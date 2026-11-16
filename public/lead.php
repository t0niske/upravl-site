<?php
/**
 * Обработчик заявок с сайта. Работает на обычном PHP-хостинге (PHP 7.4+).
 * Настройки — в lead-config.php рядом (создать из lead-config.example.php).
 * Отвечает JSON для fetch-запросов и редиректом на /spasibo/ для обычной отправки формы без JS.
 */
declare(strict_types=1);

$wantsJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

function respond(bool $ok, string $error = '', int $code = 200): void {
    global $wantsJson;
    http_response_code($code);
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'error' => $error], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . ($ok ? '/spasibo/' : '/kontakty/'), true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(false, 'method', 405);
}

$config = is_file(__DIR__ . '/lead-config.php') ? require __DIR__ . '/lead-config.php' : [];
$mailTo = $config['mail_to'] ?? '';
$tgToken = $config['telegram_bot_token'] ?? '';
$tgChat = $config['telegram_chat_id'] ?? '';

// Ловушка для ботов: скрытое поле должно быть пустым
if (!empty($_POST['website'])) {
    respond(true);
}

$clean = static function (string $key, int $max): string {
    $v = trim((string)($_POST[$key] ?? ''));
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    return mb_substr($v, 0, $max);
};

$name = $clean('name', 80);
$phone = $clean('phone', 25);
$message = $clean('message', 2000);
$topic = $clean('topic', 120);
$page = $clean('page', 200);
$quiz = $clean('quiz', 600);
$consent = !empty($_POST['consent']);

if (strlen(preg_replace('/\D/', '', $phone)) < 10) respond(false, 'phone', 422);
if (!$consent) respond(false, 'consent', 422);

// Простое ограничение частоты: не больше 5 заявок с одного IP за 10 минут
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/upravl_lead_' . md5($ip);
$hits = array_filter(
    is_file($rateFile) ? array_map('intval', explode(',', (string)file_get_contents($rateFile))) : [],
    static fn($t) => $t > time() - 600
);
if (count($hits) >= 5) respond(false, 'rate', 429);
$hits[] = time();
@file_put_contents($rateFile, implode(',', $hits));

$lines = [
    'Новая заявка с сайта upravl.ru',
    'Имя: ' . ($name !== '' ? $name : '—'),
    'Телефон: ' . $phone,
    'Тема: ' . ($topic !== '' ? $topic : '—'),
    'Страница: ' . $page,
];
if ($quiz !== '') $lines[] = $quiz;
if ($message !== '') $lines[] = "Сообщение:\n" . $message;
$lines[] = 'Время: ' . date('d.m.Y H:i');
$text = implode("\n", $lines);

$sent = false;

if ($tgToken !== '' && $tgChat !== '' && function_exists('curl_init')) {
    $ch = curl_init('https://api.telegram.org/bot' . $tgToken . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['chat_id' => $tgChat, 'text' => $text, 'disable_web_page_preview' => 'true'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);
    $res = curl_exec($ch);
    $sent = $res !== false && (json_decode((string)$res, true)['ok'] ?? false);
    curl_close($ch);
}

if ($mailTo !== '') {
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'upravl.ru');
    $headers = [
        'From: =?UTF-8?B?' . base64_encode('Сайт upravl.ru') . '?= <noreply@' . $host . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $subject = '=?UTF-8?B?' . base64_encode('Заявка: ' . ($topic !== '' ? $topic : 'консультация') . ' — ' . $phone) . '?=';
    $sent = @mail($mailTo, $subject, $text, implode("\r\n", $headers)) || $sent;
}

respond($sent, $sent ? '' : 'send', $sent ? 200 : 500);
