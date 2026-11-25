<?php

declare(strict_types=1);

// Проверка сценария бота без MAX: php bin/bot-sim.php
// Отдельная база storage/bot-sim.sqlite, в MAX ничего не уходит (MAX_BOT_DRY_RUN), ИИ — как настроен в .env.
// Печатает диалог: что «написал» человек и что ответил бот (с кнопками).

putenv('MAX_BOT_DRY_RUN=1');
putenv('DB_DSN=sqlite:' . dirname(__DIR__) . '/storage/bot-sim.sqlite');
putenv('MAX_LEADS_CHAT_ID=-100');
putenv('LEADS_MAIL_TO=');
@unlink(dirname(__DIR__) . '/storage/bot-sim.sqlite');

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Bot\Bot;
use App\Bot\MaxApi;
use App\Leads\Leads;
use App\Leads\Migrator;

if (PHP_SAPI !== 'cli') {
    exit;
}

Migrator::run();

$uid = 100500;
$ts = 1_790_000_000_000;
$last = null; // последнее сообщение бота с кнопками — для нажатий

$show = static function () use (&$last): void {
    foreach (MaxApi::$sent as $s) {
        if ($s['path'] === '/answers') {
            $t = (string) ($s['body']['message']['text'] ?? '');
            $p = mb_strrpos($t, '→');
            echo '   [кнопки убраны' . ($p !== false ? ', дописан выбор: «' . mb_substr($t, $p + 2) . '»' : '') . "]\n";
            continue;
        }
        if ($s['path'] !== '/messages') {
            continue;
        }
        $to = isset($s['query']['chat_id']) ? 'ГРУППА' : 'БОТ';
        echo "   $to: " . str_replace("\n", "\n        ", $s['body']['text']) . "\n";
        foreach ($s['body']['attachments'][0]['payload']['buttons'] ?? [] as $row) {
            echo '        [ ' . implode(' | ', array_map(static fn ($b) => $b['text'] . ($b['type'] !== 'callback' ? " ({$b['type']})" : ''), $row)) . " ]\n";
        }
        if (isset($s['query']['user_id'])) {
            $last = $s['body'];
        }
    }
    MaxApi::$sent = [];
};

$user = ['user_id' => $uid, 'first_name' => 'Ольга', 'is_bot' => false];

$start = static function (string $payload) use ($user, &$ts, $show): void {
    echo "\n>> Человек нажал «Начать»" . ($payload !== '' ? " (метка $payload)" : '') . "\n";
    Bot::handle(['update_type' => 'bot_started', 'timestamp' => $ts++, 'chat_id' => 777, 'user' => $user, 'payload' => $payload]);
    $show();
};

$say = static function (string $text, array $attachments = []) use ($user, &$ts, $show): void {
    echo "\n>> Человек: " . ($text !== '' ? $text : '[поделился контактом]') . "\n";
    Bot::handle(['update_type' => 'message_created', 'timestamp' => $ts++, 'message' => [
        'sender' => $user,
        'recipient' => ['chat_id' => 777, 'chat_type' => 'dialog'],
        'body' => ['mid' => 'm' . $ts, 'text' => $text, 'attachments' => $attachments],
    ]]);
    $show();
};

$press = static function (string $label) use ($user, &$ts, &$last, $show): void {
    $payload = null;
    foreach ($last['attachments'][0]['payload']['buttons'] ?? [] as $row) {
        foreach ($row as $b) {
            if ($b['text'] === $label && $b['type'] === 'callback') {
                $payload = $b['payload'];
            }
        }
    }
    if ($payload === null) {
        echo "\n!! Кнопки «{$label}» нет в последнем сообщении\n";
        exit(1);
    }
    echo "\n>> Человек нажал «{$label}»\n";
    Bot::handle(['update_type' => 'message_callback', 'timestamp' => $ts++,
        'callback' => ['callback_id' => 'cb' . $ts, 'payload' => $payload, 'user' => $user],
        'message' => ['body' => ['text' => $last['text'], 'attachments' => $last['attachments']]]]);
    $show();
};

// 1. Старт с сайта, квиз, запись
$start('site_kontakty');
$press('Подходит ли мне банкротство');
$press('от 500 000 до 1 000 000 ₽');
$say('а машину заберут?');
$press('нет, идёт или его нет');
$press('да, есть');
$press('нет');
$press('да');
$press('нет, не хватит');
$press('Обсудить результат бесплатно');
$say('ладно');
$press('Согласен');
$press('Ольга');
$say('12345');
$say('', [['type' => 'contact', 'payload' => ['vcf_info' => "BEGIN:VCARD\nVERSION:3.0\nFN:Ольга\nTEL;TYPE=cell:79131234567\nEND:VCARD", 'hash' => 'x']]]);
$press('Позвонить мне');
$say('после 15:00');
$say('Три банка и микрозайм, просрочка полгода, был судебный приказ.');

// 2. Вопросы, цены, контакты
$press('Задать вопрос');
$say('Что будет с ипотечной квартирой?');
$say('Сколько стоит банкротство?');
$press('Меню');
$press('Сколько стоит');
$press('Меню');
$press('Позвонить');
$say('позовите живого человека');
$say('Какая погода в Томске?');

// 3. Повторная запись в тот же день
$press('Записаться на консультацию');
$press('Ольга');
$say('+7 913 123-45-67');
$press('Онлайн: Telegram или WhatsApp');
$press('Завтра');
$press('Пропустить');

echo "\n=== CRM ===\n";
foreach (App\Support\Db::pdo()->query('SELECT id, name, phone, phone_raw, source, topic, quiz, message FROM leads')->fetchAll() as $l) {
    echo json_encode($l, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    foreach (Leads::events((int) $l['id']) as $e) {
        echo "  событие {$e['type']}: " . str_replace("\n", ' / ', (string) $e['text']) . "\n";
    }
}
