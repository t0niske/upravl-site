<?php

declare(strict_types=1);

namespace App\Bot;

use App\Leads\Log;
use App\Support\Env;

/**
 * Запросы к Bot API MAX (https://dev.max.ru/docs-api). Токен — в заголовке Authorization.
 * MAX_BOT_DRY_RUN=1 — ничего не отправляет, пишет запросы в storage/logs/bot-out.log (локальная проверка).
 */
final class MaxApi
{
    private const BASE = 'https://platform-api2.max.ru';

    /** @var list<array> отправленное в режиме проверки — для bin/bot-sim.php */
    public static array $sent = [];

    public static function dryRun(): bool
    {
        return Env::bool('MAX_BOT_DRY_RUN');
    }

    /** Сообщение человеку в личный диалог. */
    public static function sendToUser(string $userId, string $text, ?array $keyboard = null): ?array
    {
        return self::sendMessage(['user_id' => $userId], $text, $keyboard);
    }

    /** Сообщение в групповой чат (заявки). */
    public static function sendToChat(string $chatId, string $text, ?array $keyboard = null): ?array
    {
        return self::sendMessage(['chat_id' => $chatId], $text, $keyboard);
    }

    /** Ответ на нажатие кнопки: меняет исходное сообщение (убирает кнопки, дописывает выбор). */
    public static function answerCallback(string $callbackId, ?string $newText = null): void
    {
        $body = $newText !== null
            ? ['message' => ['text' => mb_substr($newText, 0, 4000), 'attachments' => []]]
            : ['notification' => ' '];
        self::request('POST', '/answers', ['callback_id' => $callbackId], $body);
    }

    /** @return array{code: int, body: ?array} */
    public static function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        if (self::dryRun()) {
            self::$sent[] = ['method' => $method, 'path' => $path, 'query' => $query, 'body' => $body];
            Log::write('bot-out.log', $method . ' ' . $path . ' ' . json_encode(['q' => $query, 'b' => $body], JSON_UNESCAPED_UNICODE));

            return ['code' => 200, 'body' => ['success' => true, 'message' => ['body' => ['mid' => 'dry']]]];
        }

        $token = (string) Env::get('MAX_BOT_TOKEN', '');

        if ($token === '' || !function_exists('curl_init')) {
            return ['code' => 0, 'body' => null];
        }

        $ch = curl_init(self::BASE . $path . ($query !== [] ? '?' . http_build_query($query) : ''));
        $headers = ['Authorization: ' . $token];

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        if ($code !== 200) {
            Log::write('bot.log', "MAX API $method $path → $code " . mb_substr((string) $raw, 0, 300));
        }

        return ['code' => $code, 'body' => is_array($decoded) ? $decoded : null];
    }

    private static function sendMessage(array $to, string $text, ?array $keyboard): ?array
    {
        $body = ['text' => mb_substr($text, 0, 4000), 'notify' => true];

        if ($keyboard !== null) {
            $body['attachments'] = [['type' => 'inline_keyboard', 'payload' => ['buttons' => $keyboard]]];
        }

        $res = self::request('POST', '/messages', $to + ['disable_link_preview' => 'true'], $body);

        return $res['code'] === 200 ? $res['body'] : null;
    }
}
