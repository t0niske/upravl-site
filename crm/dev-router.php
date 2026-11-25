<?php
// Только для локальной проверки: роутер встроенного сервера PHP вместо .htaccess.
//   php -S localhost:8797 -t <собранный сайт> crm/dev-router.php   (CRM — эта папка, если B70_CRM не задан)
if (getenv('B70_CRM') === false) {
    putenv('B70_CRM=' . __DIR__);
}
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$root = $_SERVER['DOCUMENT_ROOT'];

if (preg_match('#^/(?:crm(?:/.*)?|api/visit)$#', $path)) {
    require $root . '/crm.php';

    return true;
}

if ($path === '/lead.php') {
    require $root . '/lead.php';

    return true;
}

if (is_file($root . $path)) {
    return false;
}

if (is_file($root . rtrim($path, '/') . '/index.html')) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($root . rtrim($path, '/') . '/index.html');

    return true;
}

http_response_code(404);
readfile($root . '/404.html');

return true;
