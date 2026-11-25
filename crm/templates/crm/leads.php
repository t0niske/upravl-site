<?php

use App\Crm\View as V;
use App\Leads\Status;

/**
 * @var list<array> $leads @var array $filters @var int $total @var int $page @var int $pages
 * @var list<string> $sources @var int $newCount @var array $user
 */
$periods = ['all' => 'Всё время', 'today' => 'Сегодня', 'week' => 'Эта неделя', 'last_week' => 'Прошлая неделя', 'month' => 'Этот месяц', 'last_month' => 'Прошлый месяц', 'custom' => 'Даты…'];
?>
<div class="head">
  <h1>Заявки <span class="muted"><?= $total ?></span></h1>
  <div class="head__actions">
    <?php if ($newCount > 0): ?>
      <a class="btn btn--accent" href="/crm?status=new">Новых: <?= $newCount ?></a>
    <?php endif; ?>
    <?php if (($user['role'] ?? '') === 'manager'): ?>
      <a class="btn" href="/crm/leads.csv<?= V::e(V::query([])) ?>">Выгрузить в Excel</a>
    <?php endif; ?>
  </div>
</div>

<form class="filters" method="get" action="/crm">
  <label class="filters__search">
    <span class="sr">Поиск</span>
    <input type="search" name="q" value="<?= V::e($filters['q']) ?>" placeholder="Имя, телефон или №">
  </label>
  <label>
    <span>Статус</span>
    <select name="status">
      <option value="">все</option>
      <option value="active"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>не закрытые</option>
      <?php foreach (Status::LABELS as $key => $label): ?>
        <option value="<?= $key ?>"<?= $filters['status'] === $key ? ' selected' : '' ?>><?= V::e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>
    <span>Период</span>
    <select name="period" data-period>
      <?php foreach ($periods as $key => $label): ?>
        <option value="<?= $key ?>"<?= $filters['period'] === $key ? ' selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="filters__dates"<?= $filters['period'] === 'custom' ? '' : ' hidden' ?> data-dates>
    <span>С — по</span>
    <span class="row"><input type="date" name="from" value="<?= V::e($filters['from']) ?>"><input type="date" name="to" value="<?= V::e($filters['to']) ?>"></span>
  </label>
  <label>
    <span>Источник</span>
    <select name="source">
      <option value="">все</option>
      <?php foreach ($sources as $source): ?>
        <option<?= $filters['source'] === $source ? ' selected' : '' ?>><?= V::e($source) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div class="filters__buttons">
    <button class="btn btn--primary" type="submit">Показать</button>
    <a class="link" href="/crm">Сбросить</a>
  </div>
</form>

<?php if ($leads === []): ?>
  <div class="panel empty"><p>Заявок по этим условиям нет. Новые заявки с формы сайта появляются здесь сразу.</p></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table leads-table">
      <thead>
        <tr><th>№</th><th>Поступила</th><th>Клиент</th><th>Тема и страница</th><th>Источник</th><th>Статус</th></tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $lead): ?>
          <tr class="row-link<?= $lead['status'] === Status::NEW ? ' is-new' : '' ?>" data-href="/crm/lead/<?= $lead['id'] ?>">
            <td class="nowrap"><a href="/crm/lead/<?= $lead['id'] ?>"><?= $lead['id'] ?></a></td>
            <td class="nowrap" data-label="Поступила"><?= V::date($lead['created_at']) ?></td>
            <td data-label="Клиент">
              <a class="strong" href="/crm/lead/<?= $lead['id'] ?>"><?= V::e($lead['name'] !== '' ? $lead['name'] : 'Без имени') ?></a>
              <div class="muted nowrap"><?= V::phone($lead['phone']) ?></div>
            </td>
            <td data-label="Тема"><?= V::e($lead['topic'] ?: 'Консультация') ?><div class="muted small break"><?= V::e($lead['page_url'] ?? '') ?></div></td>
            <td data-label="Источник"><?= V::e($lead['source']) ?><?= $lead['visitor_id'] !== null ? '' : ' <span class="muted small">· без захода</span>' ?></td>
            <td data-label="Статус"><?= V::status($lead) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="pager">
      <?php if ($page > 1): ?><a class="btn" href="<?= V::e(V::query(['page' => $page - 1])) ?>">← Назад</a><?php endif; ?>
      <span class="muted">Страница <?= $page ?> из <?= $pages ?></span>
      <?php if ($page < $pages): ?><a class="btn" href="<?= V::e(V::query(['page' => $page + 1])) ?>">Дальше →</a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
<p class="muted small">Заявки приходят с формы сайта; письмо на почту и сообщение в MAX уходят как раньше. Заявки, по которым человек не стал клиентом, удаляются через 12 месяцев (152-ФЗ).</p>
