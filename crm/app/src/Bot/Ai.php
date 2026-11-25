<?php

declare(strict_types=1);

namespace App\Bot;

use App\Leads\Log;
use App\Support\Env;

/**
 * Ответы на свободные вопросы: DeepSeek (OpenAI-совместимый chat/completions) — напрямую (api.deepseek.com)
 * или через Yandex AI Studio (AI_BASE_URL=https://ai.api.cloud.yandex.net/v1 + AI_FOLDER_ID).
 * Настройки в .env: AI_API_KEY, AI_MODEL (по умолчанию deepseek-flash), AI_BASE_URL, AI_FOLDER_ID.
 * Имена и телефоны из записи в ИИ не передаются; из текста вопросов телефоны, почта и длинные номера вырезаются.
 * Нет ключа или сервис не ответил — бот показывает подходящий вопрос-ответ с сайта (answerFromSite).
 */
final class Ai
{
    public static function configured(): bool
    {
        return (string) Env::get('AI_API_KEY', '') !== '';
    }

    /**
     * @param list<array{role: string, content: string}> $history прошлые реплики диалога (по порядку)
     */
    public static function answer(string $question, array $history = []): ?string
    {
        if (!self::configured() || !function_exists('curl_init')) {
            return null;
        }

        // Yandex AI Studio — с AI_FOLDER_ID (модель gpt://<каталог>/<модель>); DeepSeek напрямую — без него.
        $folder = (string) Env::get('AI_FOLDER_ID', '');
        $model = (string) Env::get('AI_MODEL', 'deepseek-flash');
        $model = $folder !== '' && !str_starts_with($model, 'gpt://') ? "gpt://$folder/$model" : $model;
        $question = self::redact($question);

        $context = '';
        foreach (Kb::search($question . ' ' . implode(' ', array_column(array_slice($history, -2), 'content')), 7) as $c) {
            $context .= "[{$c['title']} — {$c['url']}]\n{$c['text']}\n\n";
        }

        $messages = [['role' => 'system', 'content' => self::system($context)]];
        foreach (array_slice($history, -6) as $m) {
            $messages[] = ['role' => $m['role'], 'content' => self::redact(mb_substr($m['content'], 0, 1500))];
        }
        $messages[] = ['role' => 'user', 'content' => mb_substr($question, 0, 1500)];

        $headers = ['Authorization: Bearer ' . Env::get('AI_API_KEY'), 'Content-Type: application/json'];
        if ($folder !== '') {
            $headers[] = 'OpenAI-Project: ' . $folder;
        }

        $ch = curl_init(rtrim((string) Env::get('AI_BASE_URL', 'https://api.deepseek.com'), '/') . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.2,
                'max_tokens' => 600,
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 25,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = is_string($raw) ? json_decode($raw, true) : null;
        $text = $data['choices'][0]['message']['content'] ?? null;

        if ($code !== 200 || !is_string($text) || trim($text) === '') {
            Log::write('bot.log', "ИИ не ответил: HTTP $code " . mb_substr((string) $raw, 0, 300));

            return null;
        }

        return self::clean($text);
    }

    /**
     * Без ИИ: самый похожий вопрос-ответ с сайта, если совпадение уверенное;
     * ничего похожего на сайте нет — значит, вопрос не по теме.
     */
    public static function answerFromSite(string $question): string
    {
        $found = Kb::search($question, 5);

        foreach ($found as $c) {
            if ($c['faq'] && $c['score'] >= 4.0) {
                $text = preg_replace(['/^Вопрос: /u', '/\nОтвет: /u'], ['', "\n\n"], $c['text']);

                return "На сайте об этом сказано так:\n\n" . mb_substr(trim((string) $text), 0, 1500) . "\n\nПодробнее: " . $c['url'];
            }
        }

        if ($found === [] || $found[0]['score'] < 2.5) {
            return 'Я отвечаю только на вопросы о долгах и банкротстве. Если они у вас есть — спрашивайте, а сложный случай лучше разобрать с Юрием Анатольевичем на бесплатной консультации.';
        }

        return 'Точный ответ на этот вопрос даст Юрий Анатольевич на бесплатной консультации — он разберёт именно ваш случай. Можно записаться прямо здесь или позвонить: ' . Texts::PHONE . '.';
    }

    private static function system(string $context): string
    {
        return <<<TXT
Ты — ИИ-помощник в мессенджере MAX арбитражного управляющего Антонца Юрия Анатольевича (Томск). Ты программа, а не сам Юрий Анатольевич, и не выдаёшь себя за него.
Задача: коротко и понятно отвечать на общие вопросы о банкротстве граждан, ИП и компаний и предлагать бесплатную консультацию у Юрия Анатольевича лично.

Правила:
1. Отвечай только по фактам из раздела «Сведения с сайта» ниже и из этого сообщения. Если ответа там нет — честно скажи, что точно ответит Юрий Анатольевич на бесплатной консультации. Не придумывай цифры, сроки, статьи законов, судебную практику, имена.
2. Не оценивай конкретную ситуацию человека («спишут ли мне», «заберут ли мою машину», «сколько будет стоить моё дело»): объясни общее правило и предложи разобрать его случай на консультации.
3. Не обещай результат и сроки. Цены называй только те, что есть в сведениях.
4. Не проси паспорт, СНИЛС, документы, фото, адрес. Если человек сам прислал персональные данные — не повторяй их.
5. Вопросы не о долгах, банкротстве и услугах Юрия Анатольевича вежливо отклоняй одной фразой.
6. Если человек просит живого человека, Юрия Анатольевича или юриста; если речь об уголовном деле, субсидиарной ответственности, банкротстве компании, человек — кредитор, дело уже идёт в суде, или человек пишет об угрозах — посочувствуй в одну фразу и предложи записаться на консультацию или позвонить: +7 (3822) 30-02-15.
7. Пиши по-русски, на «вы», простыми словами, без канцелярита. До 6 предложений. Без заголовков, таблиц, markdown-разметки и эмодзи. Списки — строками с «•», если без них никак.
8. В конце ответа, если уместно, одной фразой предложи бесплатную консультацию.
9. Сведения с сайта написаны от лица Юрия Анатольевича («я», «моя практика», «проведу»). Пересказывай их только в третьем лице: «у Юрия Анатольевича было такое дело», «Юрий Анатольевич проведёт». О себе говори в нейтральной форме, без «рад», «рада».

Постоянные сведения:
- Первая консультация — бесплатно. Юридическое сопровождение банкротства гражданина под ключ — от 125 000 ₽, рассрочка от 10 000 ₽ в месяц. Отдельно по закону — 25 000 ₽ на депозит суда для вознаграждения финансового управляющего и публикации в ЕФРСБ и «Коммерсанте».
- Телефоны: +7 (3822) 30-02-15, +7 (913) 889-35-36. Офис: Томск, ул. Тверская, 14, оф. 1. Пн–Пт с 9:00 до 18:00, по предварительной записи. Сайт: bankrotstvo70.ru.

Сведения с сайта:
$context
TXT;
    }

    /** Телефоны, почта, длинные номера (паспорт, СНИЛС, ИНН, счета) не уходят во внешний сервис ИИ. */
    public static function redact(string $text): string
    {
        return preg_replace([
            '/[\w.+-]+@[\w-]+\.[\w.-]+/u',
            '/(?:\+7|8)[\s(-]*\d{3}[\s)-]*\d{3}[\s-]*\d{2}[\s-]*\d{2}/u',
            '/\d[\d\s-]{8,}\d/u',
        ], ['[почта]', '[телефон]', '[номер]'], $text) ?? $text;
    }

    private static function clean(string $text): string
    {
        $text = preg_replace(['/^#{1,6}\s*/mu', '/\*\*(.+?)\*\*/u', '/__(.+?)__/u', '/^\s*[-*]\s+/mu'], ['', '$1', '$1', '• '], trim($text)) ?? $text;

        return mb_substr($text, 0, 3500);
    }
}
