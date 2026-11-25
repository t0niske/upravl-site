<?php
// Вход в CRM: админка /crm и счётчик заходов /api/visit (правило — в .htaccess).
// Файл лежит в корне сайта; код, база и настройки (.env) — вне сайта:
// на хостинге ~/bankrotstvo70-crm (рядом с ~/www), локально — путь из переменной окружения B70_CRM.
declare(strict_types=1);

$root = getenv('B70_CRM') ?: dirname(__DIR__, 2) . '/bankrotstvo70-crm';
if (!is_file($root . '/app/bootstrap.php')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'CRM временно недоступна.';
    exit;
}
require $root . '/app/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if (!App\Routes::handle($method, $path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Не найдено';
}
