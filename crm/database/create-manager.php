<?php

declare(strict_types=1);

// Создаёт сотрудника админки (по умолчанию — руководителя):
//   php database/create-manager.php логин "Имя Фамилия" [manager|admin]
// Пароль спрашивается в консоли (или берётся из переменной CRM_PASSWORD).

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Crm\Staff;
use App\Leads\Migrator;

if (PHP_SAPI !== 'cli') {
    exit;
}

[, $login, $name, $role] = $argv + [null, null, null, 'manager'];

if ($login === null || $name === null || !in_array($role, ['manager', 'admin'], true)) {
    exit("Использование: php database/create-manager.php логин \"Имя\" [manager|admin]\n");
}

Migrator::run();

$password = getenv('CRM_PASSWORD') ?: null;

if ($password === null) {
    // Ввод пароля без показа на экране (в терминале сервера по ssh -t).
    $hide = DIRECTORY_SEPARATOR === '/' && stream_isatty(STDIN);
    echo 'Пароль (не меньше ' . Staff::MIN_PASSWORD . ' символов, на экране не отображается): ';
    if ($hide) {
        shell_exec('stty -echo');
    }
    $password = trim((string) fgets(STDIN));
    if ($hide) {
        shell_exec('stty echo');
    }
    echo "\n";
}

$error = Staff::validate($login, $name, $password, true);

if ($error !== null) {
    exit($error . "\n");
}

$id = Staff::create($login, $name, $role, $password);
echo "Готово: сотрудник №{$id} «{$name}», вход по логину «{$login}».\n";
