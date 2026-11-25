<?php

declare(strict_types=1);

namespace App\Bot;

use App\Leads\Log;
use App\Support\Env;

/**
 * POST /api/max-bot — события MAX (подписка: php bin/max-webhook.php subscribe).
 * Проверяет секрет из заголовка X-Max-Bot-Api-Secret, сразу отвечает 200 (MAX ждёт не дольше 30 с),
 * потом обрабатывает событие. Повторную доставку того же события отсекает Store::seen().
 */
final class Webhook
{
    private const MAX_BYTES = 65536;

    public static function handle(string $method): void
    {
        $secret = (string) Env::get('MAX_BOT_SECRET', '');
        $given = (string) ($_SERVER['HTTP_X_MAX_BOT_API_SECRET'] ?? '');

        if ($method !== 'POST' || $secret === '' || !hash_equals($secret, $given)) {
            http_response_code($method === 'POST' ? 403 : 405);

            return;
        }

        $raw = (string) file_get_contents('php://input', false, null, 0, self::MAX_BYTES + 1);
        $update = strlen($raw) <= self::MAX_BYTES ? json_decode($raw, true) : null;

        http_response_code(200);
        header('Content-Type: application/json');
        header('Content-Length: 11');
        header('Connection: close');
        echo '{"ok":true}';
        self::flush();

        if (!is_array($update)) {
            return;
        }

        try {
            if (!Store::seen(self::id($update))) {
                Bot::handle($update);
            }
        } catch (\Throwable $e) {
            Log::error('Ошибка бота MAX (' . ($update['update_type'] ?? '?') . ')', $e);
        }
    }

    /** Отпустить MAX, пока бот думает (ИИ может отвечать 5–20 секунд). */
    private static function flush(): void
    {
        ignore_user_abort(true);
        set_time_limit(60);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } else {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            flush();
        }
    }

    private static function id(array $u): string
    {
        $key = ($u['update_type'] ?? '') . '|' . ($u['timestamp'] ?? '') . '|'
            . ($u['callback']['callback_id'] ?? $u['message']['body']['mid'] ?? $u['user']['user_id'] ?? $u['chat_id'] ?? '');

        return hash('sha256', $key);
    }
}
