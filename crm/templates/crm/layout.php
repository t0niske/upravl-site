<?php

use App\Crm\Staff;
use App\Crm\View as V;

/** @var string $title @var string $content @var string $active @var array $user @var ?array $flash */

$nav = [
    'leads' => ['/crm', 'Заявки'],
    'visits' => ['/crm/visits', 'Заходы'],
];

if (($user['role'] ?? '') === 'manager') {
    $nav += [
        'reports' => ['/crm/reports', 'Отчёты'],
        'channel' => ['/crm/channel', 'Канал'],
        'staff' => ['/crm/staff', 'Сотрудники'],
        'audit' => ['/crm/audit', 'Журнал'],
    ];
}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#034b8e">
<title><?= V::e($title) ?> — CRM bankrotstvo70.ru</title>
<link rel="icon" href="/crm/assets/icon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/crm/assets/crm.css?v=1">
<script src="/crm/assets/crm.js?v=1" defer></script>
</head>
<body>
<header class="top">
  <div class="top__inner">
    <a class="logo" href="/crm"><span class="logo__mark">АЮ</span><span class="logo__text">bankrotstvo70.ru: заявки</span></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="nav" data-menu>Меню</button>
    <nav class="nav" id="nav">
      <?php foreach ($nav as $key => [$href, $label]): ?>
        <a href="<?= $href ?>" class="nav__link<?= $active === $key ? ' is-active' : '' ?>"><?= V::e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="me">
      <a href="/crm/profile" class="me__name<?= $active === 'profile' ? ' is-active' : '' ?>"><?= V::e($user['name']) ?> · <?= V::e(Staff::ROLES[$user['role']] ?? '') ?></a>
      <form method="post" action="/crm/logout"><?= V::csrf() ?><button class="link" type="submit">Выйти</button></form>
    </div>
  </div>
</header>
<main class="page">
  <?php if ($flash): ?>
    <div class="flash flash--<?= V::e($flash['type']) ?>" role="status"><?= V::e($flash['text']) ?></div>
  <?php endif; ?>
  <?= $content ?>
</main>
</body>
</html>
