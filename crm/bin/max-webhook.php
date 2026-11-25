<?php

declare(strict_types=1);

// Подписка бота MAX на события:
//   php bin/max-webhook.php status       — текущие подписки
//   php bin/max-webhook.php subscribe    — вебхук APP_URL/api/max-bot с секретом MAX_BOT_SECRET
//   php bin/max-webhook.php unsubscribe  — отключить вебхук (бот перестанет отвечать)

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Bot\MaxApi;
use App\Support\Env;

if (PHP_SAPI !== 'cli') {
    exit;
}

$cmd = $argv[1] ?? 'status';
$url = rtrim((string) Env::get('APP_URL'), '/') . '/api/max-bot';
$types = ['bot_started', 'bot_stopped', 'bot_added', 'message_created', 'message_callback'];

$res = match ($cmd) {
    'subscribe' => MaxApi::request('POST', '/subscriptions', [], ['url' => $url, 'update_types' => $types, 'secret' => (string) Env::get('MAX_BOT_SECRET')]),
    'unsubscribe' => MaxApi::request('DELETE', '/subscriptions', ['url' => $url]),
    default => MaxApi::request('GET', '/subscriptions'),
};

echo "HTTP {$res['code']}\n" . json_encode($res['body'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
