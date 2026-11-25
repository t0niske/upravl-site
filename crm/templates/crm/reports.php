<?php

use App\Crm\ReportsController as R;
use App\Crm\View as V;
use App\Leads\Status;

/**
 * @var string $period @var string $fromValue @var string $toValue @var DateTimeImmutable $from @var DateTimeImmutable $to
 * @var int $total @var array<string, int> $statuses @var array $sources @var array $site
 */
$periods = ['month' => 'Этот месяц', 'last_month' => 'Прошлый месяц', 'week' => 'Эта неделя', 'last_week' => 'Прошлая неделя', 'days30' => 'Последние 30 дней', 'custom' => 'Даты…'];
$base = $total - $statuses[Status::JUNK];
$maxSource = max([1, ...array_column($sources, 'total')]);
?>
<div class="head">
  <h1>Отчёты <span class="muted"><?= $from->format('d.m.Y') ?> — <?= $to->modify('-1 day')->format('d.m.Y') ?></span></h1>
</div>

<form class="filters filters--compact" method="get" action="/crm/reports">
  <label><span>Период</span>
    <select name="period" data-period>
      <?php foreach ($periods as $key => $label): ?>
        <option value="<?= $key ?>"<?= $period === $key ? ' selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="filters__dates"<?= $period === 'custom' ? '' : ' hidden' ?> data-dates>
    <span>С — по</span>
    <span class="row"><input type="date" name="from" value="<?= V::e($fromValue) ?>"><input type="date" name="to" value="<?= V::e($toValue) ?>"></span>
  </label>
  <div class="filters__buttons"><button class="btn btn--primary">Показать</button></div>
</form>

<h2 class="section-title">Заявки</h2>
<div class="tiles">
  <div class="tile"><span class="tile__label">Заявок</span><span class="tile__value"><?= $total ?></span><span class="tile__note">спам: <?= $statuses[Status::JUNK] ?></span></div>
  <div class="tile"><span class="tile__label">Новые и в работе</span><span class="tile__value"><?= $statuses[Status::NEW] + $statuses[Status::IN_WORK] ?></span><span class="tile__note">новых: <?= $statuses[Status::NEW] ?></span></div>
  <div class="tile tile--accent"><span class="tile__label">Заявка → клиент</span><span class="tile__value"><?= R::percent($statuses[Status::CLIENT], $base) ?></span><span class="tile__note">стали клиентами: <?= $statuses[Status::CLIENT] ?></span></div>
  <div class="tile"><span class="tile__label">Отказ</span><span class="tile__value"><?= $statuses[Status::REFUSED] ?></span><span class="tile__note"><?= R::percent($statuses[Status::REFUSED], $base) ?> от заявок без спама</span></div>
</div>

<div class="report-grid">
  <section class="panel">
    <h2>Источники заявок</h2>
    <?php if ($sources === []): ?><p class="muted">Заявок нет.</p><?php endif; ?>
    <table class="table table--compact">
      <?php if ($sources !== []): ?><tr><th>Источник</th><th></th><th class="num">Заявок</th><th class="num">Клиентов</th></tr><?php endif; ?>
      <?php foreach ($sources as $source => $row): ?>
        <tr>
          <td><?= V::e($source) ?></td>
          <td class="bar-cell"><span class="bar"><span class="bar__fill" style="width: <?= round($row['total'] * 100 / $maxSource) ?>%"></span></span></td>
          <td class="num"><?= $row['total'] ?></td>
          <td class="num"><?= $row['client'] ?> <span class="muted small"><?= R::percent($row['client'], $row['total']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </section>
</div>

<h2 class="section-title">Сайт <span class="muted small">сейчас на сайте: <?= $site['online'] ?> · <a href="/crm/visits">все заходы</a></span></h2>
<div class="tiles">
  <div class="tile"><span class="tile__label">Людей</span><span class="tile__value"><?= $site['people'] ?></span><span class="tile__note">заходов: <?= $site['visits'] ?>, ботов отсеяно: <?= $site['bots'] ?></span></div>
  <div class="tile"><span class="tile__label">Заявок за период</span><span class="tile__value"><?= $site['leads'] ?></span><span class="tile__note">с заходом на сайт: <?= $site['visits_with_lead'] ?></span></div>
  <div class="tile tile--accent"><span class="tile__label">Конверсия сайта</span><span class="tile__value"><?= R::percent($site['visits_with_lead'], $site['people']) ?></span><span class="tile__note">заявок с сайта на число людей</span></div>
  <div class="tile"><span class="tile__label">Нажали на телефон / MAX</span><span class="tile__value"><?= $site['tel'] ?> / <?= $site['max'] ?></span><span class="tile__note">звонки с сайта заявкой не считаются</span></div>
</div>

<div class="report-grid">
  <section class="panel">
    <h2>Откуда приходят</h2>
    <?php if ($site['sources'] === []): ?><p class="muted">Заходов нет.</p><?php endif; ?>
    <table class="table table--compact">
      <?php if ($site['sources'] !== []): ?><tr><th>Источник</th><th class="num">Людей</th><th class="num">Заходов</th><th class="num">Заявок</th></tr><?php endif; ?>
      <?php foreach ($site['sources'] as $source => $row): ?>
        <tr><td><?= V::e($source) ?></td><td class="num"><?= $row['people'] ?></td><td class="num"><?= $row['visits'] ?></td><td class="num"><?= $row['leads'] ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="panel">
    <h2>Страницы входа</h2>
    <?php if ($site['landings'] === []): ?><p class="muted">Пока нет данных.</p><?php endif; ?>
    <table class="table table--compact">
      <?php foreach ($site['landings'] as $path => $row): ?>
        <tr><td class="break"><?= V::e($path) ?></td><td class="num"><?= $row['visits'] ?></td><td class="num muted small"><?= $row['leads'] ? $row['leads'] . ' заяв.' : '' ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="panel">
    <h2>После каких страниц оставляют заявку</h2>
    <?php if ($site['lead_pages'] === []): ?><p class="muted">Пока нет данных.</p><?php endif; ?>
    <table class="table table--compact">
      <?php foreach ($site['lead_pages'] as $path => $count): ?>
        <tr><td class="break"><?= V::e($path) ?></td><td class="num"><?= $count ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="panel">
    <h2>Где уходят без заявки</h2>
    <?php if ($site['exit_pages'] === []): ?><p class="muted">Пока нет данных.</p><?php endif; ?>
    <table class="table table--compact">
      <?php foreach ($site['exit_pages'] as $path => $count): ?>
        <tr><td class="break"><?= V::e($path) ?></td><td class="num"><?= $count ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section>
</div>
