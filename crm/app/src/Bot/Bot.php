<?php

declare(strict_types=1);

namespace App\Bot;

use App\Leads\Clock;
use App\Leads\Leads;
use App\Leads\Log;
use App\Leads\Phone;
use App\Leads\Status;
use App\Support\Env;

/**
 * Сценарий бота MAX (design/max-bot/scenarij-bota.md): меню, квиз, запись на консультацию, цены, контакты, вопросы к ИИ.
 * Работает только в личных диалогах; события из групп (кроме добавления бота) игнорирует.
 */
final class Bot
{
    private const SOURCES = [
        'quiz_site' => 'MAX-бот: квиз на сайте',
        'channel' => 'MAX-бот: канал',
        'qr_office' => 'MAX-бот: QR в офисе',
    ];

    public static function handle(array $u): void
    {
        $type = (string) ($u['update_type'] ?? '');

        match ($type) {
            'bot_started' => self::started($u),
            'message_created' => self::message($u),
            'message_callback' => self::callback($u),
            'bot_stopped' => self::stopped($u),
            'bot_added' => self::added($u),
            default => null,
        };
    }

    // --- События ---

    private static function started(array $u): void
    {
        $from = $u['user'] ?? [];
        $id = (string) ($from['user_id'] ?? '');

        if ($id === '') {
            return;
        }

        $payload = self::payload((string) ($u['payload'] ?? ''));
        $user = Store::user($id, self::name($from), self::source($payload));

        $fields = ['state' => 'menu', 'stopped' => 0, 'chat_id' => isset($u['chat_id']) ? (string) $u['chat_id'] : $user['chat_id']];
        if ($payload !== '' && empty($user['lead_id'])) {
            $fields['source'] = self::source($payload);
        }
        Store::update((int) $user['id'], $fields);

        if ($payload === 'quiz_site') {
            self::send($user, Texts::greeting(true), [[Texts::cb('Записаться на консультацию', 'book')], [Texts::cb('Меню', 'menu')]]);

            return;
        }

        self::send($user, Texts::greeting());
        self::send($user, Texts::menuPrompt(), Texts::menu());
    }

    private static function message(array $u): void
    {
        $msg = $u['message'] ?? [];
        $from = $msg['sender'] ?? [];

        if (($msg['recipient']['chat_type'] ?? '') !== 'dialog' || !empty($from['is_bot']) || empty($from['user_id'])) {
            return;
        }

        $user = Store::user((string) $from['user_id'], self::name($from));
        $chatId = isset($msg['recipient']['chat_id']) ? (string) $msg['recipient']['chat_id'] : null;

        if ($chatId !== null && $chatId !== (string) $user['chat_id']) {
            Store::update((int) $user['id'], ['chat_id' => $chatId]);
            $user['chat_id'] = $chatId;
        }

        $text = trim((string) ($msg['body']['text'] ?? ''));
        $contact = self::contact($msg['body']['attachments'] ?? []);

        if ($text === '' && $contact === null) {
            self::send($user, 'Пока я понимаю только текст. Напишите вопрос словами или выберите кнопку.', Texts::menu());

            return;
        }

        if (preg_match('/^(\/start|\/menu|start|старт|начать|меню|отмена|в меню)$/iu', $text)) {
            self::menu($user);

            return;
        }

        match ($user['state']) {
            'book_consent' => self::send($user, 'Чтобы записать вас, нужно согласие на обработку имени и телефона. Нажмите «Согласен» или вернитесь в меню.', self::consentKeyboard()),
            'book_name' => self::bookName($user, $text),
            'book_phone' => self::bookPhone($user, $text, $contact),
            'book_how' => self::bookHow($user, mb_substr($text, 0, 100)),
            'book_when' => self::bookWhen($user, mb_substr($text, 0, 100)),
            'book_about' => self::bookFinish($user, mb_substr($text, 0, 1500)),
            'quiz' => self::quizRemind($user),
            default => $text === '' ? self::menu($user) : self::question($user, $text),
        };
    }

    private static function callback(array $u): void
    {
        $cb = $u['callback'] ?? [];
        $from = $cb['user'] ?? [];
        $payload = (string) ($cb['payload'] ?? '');

        if (empty($from['user_id']) || $payload === '') {
            return;
        }

        // Убираем кнопки у нажатого сообщения и дописываем выбор — история диалога остаётся читаемой.
        if (!empty($cb['callback_id'])) {
            $orig = (string) ($u['message']['body']['text'] ?? '');
            $label = self::buttonLabel($u['message']['body']['attachments'] ?? [], $payload);
            MaxApi::answerCallback((string) $cb['callback_id'], $orig !== '' ? $orig . ($label !== null ? "\n\n→ $label" : '') : null);
        }

        $user = Store::user((string) $from['user_id'], self::name($from));

        if ($user['stopped']) {
            Store::update((int) $user['id'], ['stopped' => 0]);
        }

        [$action, $arg] = array_pad(explode(':', $payload, 2), 2, '');

        match ($action) {
            'menu' => self::menu($user),
            'quiz' => self::quizStart($user),
            'q' => self::quizAnswer($user, $arg),
            'book' => self::bookStart($user),
            'consent' => self::bookConsent($user),
            'name' => self::bookName($user, (string) $user['name']),
            'how' => self::bookHow($user, ['call' => 'позвонить', 'office' => 'приду в офис', 'online' => 'онлайн (Telegram или WhatsApp)'][$arg] ?? $arg),
            'when' => self::bookWhen($user, ['today' => 'сегодня', 'tomorrow' => 'завтра', 'soon' => 'в ближайшие дни', 'any' => 'в любое время'][$arg] ?? $arg),
            'skip' => $user['state'] === 'book_about' ? self::bookFinish($user, '') : self::menu($user),
            'ask' => self::ask($user),
            'price' => self::send($user, Texts::price(), Texts::priceKeyboard()),
            'contacts' => self::send($user, Texts::contacts(), Texts::contactsKeyboard()),
            default => self::menu($user),
        };
    }

    /** Бота добавили в группу или канал. Канал запоминаем — туда пойдут посты по расписанию. */
    private static function added(array $u): void
    {
        $chatId = (string) ($u['chat_id'] ?? '');
        Log::write('bot.log', 'Бот добавлен в ' . (!empty($u['is_channel']) ? 'канал ' : 'чат ') . $chatId);

        if ($chatId !== '' && !empty($u['is_channel'])) {
            Channel::remember($chatId);
        }
    }

    private static function stopped(array $u): void
    {
        $id = (string) ($u['user']['user_id'] ?? '');

        if ($id !== '') {
            $user = Store::user($id);
            Store::update((int) $user['id'], ['stopped' => 1, 'state' => 'menu']);
        }
    }

    // --- Меню, цены, вопрос ---

    private static function menu(array $user): void
    {
        Store::update((int) $user['id'], ['state' => 'menu']);
        self::send($user, Texts::menuPrompt(), Texts::menu());
    }

    private static function ask(array $user): void
    {
        Store::update((int) $user['id'], ['state' => 'ask']);
        self::send($user, Texts::askPrompt(), [[Texts::cb('Меню', 'menu')]]);
    }

    private static function question(array $user, string $text): void
    {
        $text = mb_substr($text, 0, 1500);

        if (preg_match('/перезвон|позвоните мне|запиш(ите|у|ет)|записаться|хочу на консульт/iu', $text)) {
            Store::message((int) $user['id'], 'in', $text);
            self::bookStart($user);

            return;
        }

        if (preg_match('/жив(ой|ого|ым) (человек|юрист)|оператор|с (юристом|человеком)|позовите|соедините/iu', $text)) {
            Store::message((int) $user['id'], 'in', $text);
            self::send($user, "Конечно. Юрий Анатольевич консультирует сам, без помощников.\n\nПозвоните: " . Texts::PHONE . ' (' . Texts::HOURS . ') или оставьте номер — он перезвонит.', Texts::afterAnswer());

            return;
        }

        // Короткий вопрос о цене — готовый текст с сайта: точнее и бесплатно.
        if (mb_strlen($text) < 80 && preg_match('/сколько (стоит|стоят|будет стоить)|какая цена|стоимост|расценк|прайс|почём|по чем/iu', $text)) {
            Store::message((int) $user['id'], 'in', $text);
            self::send($user, Texts::price(), Texts::priceKeyboard());

            return;
        }

        // Лимит вопросов к ИИ в сутки на человека — чтобы расходы не росли без контроля.
        $today = Clock::now()->format('Y-m-d');
        $count = $user['ai_day'] === $today ? (int) $user['ai_count'] : 0;
        $limit = (int) Env::get('AI_DAILY_LIMIT', '15');

        if ($count >= $limit) {
            self::send($user, 'На сегодня вопросов много — лучше обсудить всё с Юрием Анатольевичем на бесплатной консультации. Он разберёт именно ваш случай.', Texts::afterAnswer());

            return;
        }

        Store::update((int) $user['id'], ['ai_day' => $today, 'ai_count' => $count + 1]);
        self::typing($user);

        $history = [];
        foreach (Store::messages((int) $user['id'], 8) as $m) {
            if ($m['dir'] === 'in' || $m['dir'] === 'ai') {
                $history[] = ['role' => $m['dir'] === 'in' ? 'user' : 'assistant', 'content' => (string) $m['text']];
            }
        }

        Store::message((int) $user['id'], 'in', $text);
        $answer = Ai::answer($text, $history) ?? Ai::answerFromSite($text);

        Store::message((int) $user['id'], 'ai', $answer);
        MaxApi::sendToUser((string) $user['max_user_id'], $answer, Texts::afterAnswer());
    }

    // --- Квиз ---

    private static function quizStart(array $user): void
    {
        $data = Store::data($user);
        $data['quiz'] = [];
        $data['quiz_step'] = 0;
        Store::update((int) $user['id'], ['state' => 'quiz', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);

        self::send($user, '6 вопросов, ответы кнопками. Результат предварительный: точный ответ Юрий Анатольевич даст после разбора документов на консультации.');
        self::quizAsk($user, 0);
    }

    private static function quizAsk(array $user, int $step): void
    {
        $q = Quiz::QUESTIONS[$step];
        $rows = array_map(static fn (array $o): array => [Texts::cb($o[1], "q:$step:{$o[0]}")], $q['options']);

        self::send($user, sprintf("Вопрос %d из %d\n\n%s\n\n%s", $step + 1, Quiz::count(), $q['q'], $q['hint']), $rows);
    }

    private static function quizAnswer(array $user, string $arg): void
    {
        [$step, $value] = array_pad(explode(':', $arg, 2), 2, '');
        $step = (int) $step;
        $data = Store::data($user);

        // Нажали кнопку старого вопроса — молча пропускаем.
        if ($user['state'] !== 'quiz' || $step !== (int) ($data['quiz_step'] ?? -1) || Quiz::label($step, $value) === null) {
            return;
        }

        $data['quiz'][Quiz::QUESTIONS[$step]['name']] = $value;
        $data['quiz_step'] = $step + 1;

        if ($step + 1 < Quiz::count() && !($step === 0 && $value === 'lt25')) {
            Store::setData((int) $user['id'], $data);
            self::quizAsk($user, $step + 1);

            return;
        }

        $data['quiz_done'] = Quiz::summary($data['quiz']);
        Store::update((int) $user['id'], ['state' => 'menu', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);

        $r = Quiz::decide($data['quiz']);
        $text = $r['title'] . "\n\n" . $r['text'] . '.';
        foreach ($r['notes'] as $n) {
            $text .= "\n\n• " . $n . '.';
        }

        $rows = [[Texts::cb('Обсудить результат бесплатно', 'book')], [Texts::cb('Задать вопрос', 'ask'), Texts::cb('Пройти заново', 'quiz')]];
        if ($r['link'] !== null) {
            $rows[] = [Texts::link($r['link'][1], Texts::SITE . $r['link'][0])];
        }

        self::send($user, $text, $rows);
    }

    private static function quizRemind(array $user): void
    {
        $step = (int) (Store::data($user)['quiz_step'] ?? 0);
        self::send($user, 'Выберите ответ кнопкой — так я точнее посчитаю результат. Вопрос можно задать после теста.');
        self::quizAsk($user, min($step, Quiz::count() - 1));
    }

    // --- Запись на консультацию ---

    private static function bookStart(array $user): void
    {
        $data = Store::data($user);
        $data['book'] = [];
        Store::setData((int) $user['id'], $data);

        if (!empty($user['consent_at'])) {
            self::askName($user);

            return;
        }

        Store::update((int) $user['id'], ['state' => 'book_consent']);
        self::send(
            $user,
            "Чтобы Юрий Анатольевич связался с вами, мне нужны имя и телефон.\n\nНажимая «Согласен», вы даёте согласие на обработку персональных данных: " . Texts::CONSENT_URL,
            self::consentKeyboard(),
        );
    }

    private static function consentKeyboard(): array
    {
        return [[Texts::cb('Согласен', 'consent'), Texts::cb('Назад', 'menu')]];
    }

    private static function bookConsent(array $user): void
    {
        if ($user['state'] !== 'book_consent') {
            return;
        }

        Store::update((int) $user['id'], ['consent_at' => Clock::sql()]);
        self::askName($user);
    }

    private static function askName(array $user): void
    {
        Store::update((int) $user['id'], ['state' => 'book_name']);
        $rows = [[Texts::cb('Отмена', 'menu')]];

        if (trim((string) $user['name']) !== '') {
            array_unshift($rows, [Texts::cb(mb_substr((string) $user['name'], 0, 40), 'name')]);
        }

        self::send($user, 'Как к вам обращаться? Напишите имя' . (trim((string) $user['name']) !== '' ? ' или нажмите кнопку.' : '.'), $rows);
    }

    private static function bookName(array $user, string $text): void
    {
        if ($user['state'] !== 'book_name') {
            return;
        }

        $name = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 60 || preg_match('/\d{3,}/', $name)) {
            self::send($user, 'Напишите, пожалуйста, только имя — например, «Ольга».', [[Texts::cb('Отмена', 'menu')]]);

            return;
        }

        $data = Store::data($user);
        $data['book']['name'] = $name;
        Store::update((int) $user['id'], ['state' => 'book_phone', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);

        self::send($user, "Приятно познакомиться, $name! Оставьте номер телефона: нажмите кнопку или напишите номер.", [
            [['type' => 'request_contact', 'text' => 'Отправить мой номер']],
            [Texts::cb('Отмена', 'menu')],
        ]);
    }

    /** @param ?array{phone: string, verified: bool} $contact */
    private static function bookPhone(array $user, string $text, ?array $contact): void
    {
        $phone = $contact['phone'] ?? $text;
        $normalized = Phone::normalize($phone);

        if ($normalized === null) {
            self::send($user, 'Не похоже на номер телефона. Напишите номер полностью, например +7 913 123-45-67, или нажмите кнопку.', [
                [['type' => 'request_contact', 'text' => 'Отправить мой номер']],
                [Texts::cb('Отмена', 'menu')],
            ]);

            return;
        }

        $data = Store::data($user);
        $data['book']['phone'] = $normalized;
        $data['book']['phone_verified'] = (bool) ($contact['verified'] ?? false);
        Store::update((int) $user['id'], ['state' => 'book_how', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);

        self::send($user, 'Как удобнее поговорить?', [
            [Texts::cb('Позвонить мне', 'how:call')],
            [Texts::cb('Приду в офис', 'how:office')],
            [Texts::cb('Онлайн: Telegram или WhatsApp', 'how:online')],
        ]);
    }

    private static function bookHow(array $user, string $how): void
    {
        if ($user['state'] !== 'book_how' || trim($how) === '') {
            return;
        }

        $data = Store::data($user);
        $data['book']['how'] = $how;
        Store::update((int) $user['id'], ['state' => 'book_when', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);

        self::send($user, 'Когда удобно? Можно написать своё время, например «после 15:00».', [
            [Texts::cb('Сегодня', 'when:today'), Texts::cb('Завтра', 'when:tomorrow')],
            [Texts::cb('В ближайшие дни', 'when:soon'), Texts::cb('В любое время', 'when:any')],
        ]);
    }

    private static function bookWhen(array $user, string $when): void
    {
        if ($user['state'] !== 'book_when' || trim($when) === '') {
            return;
        }

        $data = Store::data($user);
        $data['book']['when'] = $when;
        Store::update((int) $user['id'], ['state' => 'book_about', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);

        self::send($user, 'И последнее, по желанию: коротко опишите ситуацию — сколько кредиторов, есть ли просрочки или суды. Так консультация будет предметнее.', [
            [Texts::cb('Пропустить', 'skip')],
        ]);
    }

    private static function bookFinish(array $user, string $about): void
    {
        if ($user['state'] !== 'book_about') {
            return;
        }

        $data = Store::data($user);
        $book = $data['book'] ?? [];

        if (empty($book['phone'])) {
            self::bookStart($user);

            return;
        }

        $book['about'] = trim($about);
        $questions = array_values(array_map(
            static fn (array $m): string => mb_substr((string) $m['text'], 0, 200),
            array_filter(Store::messages((int) $user['id'], 30), static fn (array $m): bool => $m['dir'] === 'in'),
        ));

        $details = array_filter([
            'Как связаться: ' . ($book['how'] ?? '—') . "\nКогда удобно: " . ($book['when'] ?? '—'),
            $book['about'] !== '' ? "Ситуация:\n" . $book['about'] : null,
            $questions !== [] ? "Спрашивал(а) в боте:\n• " . implode("\n• ", array_slice($questions, -5)) : null,
        ]);
        $message = implode("\n\n", $details);
        $quiz = $data['quiz_done'] ?? null;

        // Повторная запись в течение суток — не новая заявка, а дополнение к прежней.
        $leadId = (int) ($user['lead_id'] ?? 0);
        $lead = $leadId > 0 ? Leads::find($leadId) : null;
        $repeat = $lead !== null && strtotime((string) $lead['created_at']) > Clock::now()->getTimestamp() - 86400;

        try {
            if ($repeat) {
                Leads::event($leadId, 'comment', null, "Повторная запись из бота MAX\nТелефон: {$book['phone']}\n" . $message);
                Leads::update($leadId, []);
            } else {
                $leadId = Leads::insert([
                    'created_at' => Clock::sql(),
                    'updated_at' => Clock::sql(),
                    'name' => mb_substr((string) ($book['name'] ?? ''), 0, 100),
                    'phone' => $book['phone'],
                    'phone_raw' => $book['phone'] . (!empty($book['phone_verified']) ? ' (подтверждён в MAX)' : ''),
                    'topic' => 'Консультация (бот MAX)',
                    'message' => mb_substr($message, 0, 2000),
                    'quiz' => $quiz !== null ? mb_substr($quiz, 0, 600) : null,
                    'source' => (string) $user['source'],
                    'status' => Status::NEW,
                ]);
                Leads::event($leadId, 'created', null, 'Заявка из бота MAX');
            }
        } catch (\Throwable $e) {
            Log::error('Заявка из бота не записана в CRM', $e);
            $leadId = 0;
        }

        unset($data['book']);
        Store::update((int) $user['id'], [
            'state' => 'menu',
            'name' => mb_substr((string) ($book['name'] ?? $user['name']), 0, 100),
            'lead_id' => $leadId > 0 ? $leadId : $user['lead_id'],
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);

        Notify::lead($leadId, $book, $message, $quiz, (string) $user['source'], $repeat);

        $num = $leadId > 0 ? " №$leadId" : '';
        self::send(
            $user,
            "Заявка$num принята. " . Texts::whenCallback(Clock::now()) . "\n\nЕсли срочно — звоните: " . Texts::PHONE . '.',
            [[Texts::cb('Задать вопрос', 'ask'), Texts::cb('Меню', 'menu')]],
        );
    }

    // --- Вспомогательное ---

    private static function send(array $user, string $text, ?array $keyboard = null): void
    {
        Store::message((int) $user['id'], 'out', $text);
        MaxApi::sendToUser((string) $user['max_user_id'], $text, $keyboard);
    }

    private static function typing(array $user): void
    {
        if (!empty($user['chat_id'])) {
            MaxApi::request('POST', '/chats/' . rawurlencode((string) $user['chat_id']) . '/actions', [], ['action' => 'typing_on']);
        }
    }

    private static function name(array $from): string
    {
        $name = trim((string) ($from['first_name'] ?? $from['name'] ?? ''));

        return mb_substr($name, 0, 100);
    }

    /** Метка из ссылки max.ru/<бот>?start=<метка>: только латиница, цифры, _ и -. */
    private static function payload(string $raw): string
    {
        return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $raw) ? $raw : '';
    }

    private static function source(string $payload): string
    {
        if ($payload === '') {
            return 'MAX-бот';
        }

        if (isset(self::SOURCES[$payload])) {
            return self::SOURCES[$payload];
        }

        return str_starts_with($payload, 'site') ? 'MAX-бот: сайт' . (strlen($payload) > 5 ? ' (' . substr($payload, 5) . ')' : '') : 'MAX-бот: ' . $payload;
    }

    /** @return ?array{phone: string, verified: bool} номер из кнопки «Отправить мой номер» */
    private static function contact(array $attachments): ?array
    {
        foreach ($attachments as $a) {
            if (($a['type'] ?? '') !== 'contact') {
                continue;
            }

            $vcf = (string) ($a['payload']['vcf_info'] ?? '');
            if (!preg_match('/^TEL[^:]*:(.+)$/mi', $vcf, $m)) {
                continue;
            }

            $hash = (string) ($a['payload']['hash'] ?? '');
            $token = (string) Env::get('MAX_BOT_TOKEN', '');
            $verified = $hash !== '' && $token !== ''
                && (hash_equals(hash_hmac('sha256', $vcf, $token), $hash) || hash_equals(hash_hmac('sha256', $token, $vcf), $hash));

            return ['phone' => trim($m[1]), 'verified' => $verified];
        }

        return null;
    }

    private static function buttonLabel(array $attachments, string $payload): ?string
    {
        foreach ($attachments as $a) {
            foreach ($a['payload']['buttons'] ?? [] as $row) {
                foreach ($row as $b) {
                    if (($b['payload'] ?? null) === $payload) {
                        return (string) $b['text'];
                    }
                }
            }
        }

        return null;
    }
}
