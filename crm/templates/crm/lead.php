<?php

use App\Crm\View as V;
use App\Leads\Status;
use App\Visits\Visits;

/**
 * @var array $lead @var list<array> $events @var list<array> $visits @var ?array $timeline @var ?array $ownVisit
 * @var list<array> $samePhone @var array $user
 */
$id = (int) $lead['id'];
$action = static fn (string $name): string => "/crm/lead/$id/$name";
$phoneHref = 'tel:' . preg_replace('/[^\d+]/', '', (string) $lead['phone']);
?>
<a class="back link" href="/crm">← Все заявки</a>

<section class="panel">
  <div class="lead-head__title">
    <h1>Заявка №<?= $id ?> · <?= V::e($lead['name'] !== '' ? $lead['name'] : 'Без имени') ?></h1>
    <div><?= V::status($lead) ?></div>
  </div>
  <p class="status-line">
    <a class="strong" href="<?= V::e($phoneHref) ?>"><?= V::phone($lead['phone']) ?></a>
    · поступила <?= V::e(V::date($lead['created_at'])) ?> · <?= V::e($lead['source']) ?>
  </p>
  <form class="inline status-form" method="post" action="<?= $action('status') ?>">
    <?= V::csrf() ?>
    <?php foreach (Status::LABELS as $key => $label): ?>
      <button class="btn<?= $lead['status'] === $key ? ' btn--primary' : '' ?>" name="status" value="<?= $key ?>"<?= $lead['status'] === $key ? ' aria-pressed="true"' : '' ?>><?= V::e($label) ?></button>
    <?php endforeach; ?>
  </form>
</section>

<div class="lead-grid">
  <div class="lead-main">
    <section class="panel">
      <h2>Что оставил</h2>
      <dl class="props">
        <dt>Тема</dt><dd><?= V::e($lead['topic'] ?: 'Консультация') ?></dd>
        <dt>Страница</dt><dd class="break"><?= V::e($lead['page_url'] ?: '—') ?></dd>
        <?php if ($lead['quiz']): ?><dt>Ответы теста</dt><dd><?= nl2br(V::e($lead['quiz'])) ?></dd><?php endif; ?>
        <?php if ($lead['message']): ?><dt>Сообщение</dt><dd><?= nl2br(V::e($lead['message'])) ?></dd><?php endif; ?>
        <?php if ($lead['phone_raw'] && preg_replace('/\D/', '', $lead['phone_raw']) !== preg_replace('/\D/', '', $lead['phone'])): ?>
          <dt>Телефон как ввёл</dt><dd><?= V::e($lead['phone_raw']) ?></dd>
        <?php endif; ?>
      </dl>
    </section>

    <section class="panel">
      <h2>Как действовал на сайте</h2>
      <?php if ($timeline === null): ?>
        <p class="muted small">Заход на сайт с этой заявкой не связан: заявка отправлена до подключения счётчика, с заблокированными cookie или не с сайта.</p>
      <?php else: ?>
        <?php $start = strtotime($ownVisit['started_at']); ?>
        <p class="small">Заход <a href="/crm/visit/<?= (int) $ownVisit['id'] ?>">№<?= (int) $ownVisit['id'] ?></a>:
          <?= V::e($ownVisit['source']) ?>, <?= (int) $ownVisit['pages'] ?> стр., на экране <?= V::duration((int) $ownVisit['active_seconds']) ?>,
          <?= V::e($ownVisit['device']) ?>.</p>
        <ol class="timeline">
          <?php foreach ($timeline['pages'] as $page): ?>
            <li class="timeline__item">
              <time>+<?= V::duration(max(0, strtotime($page['opened_at']) - $start)) ?></time>
              <div>
                <strong class="break"><?= V::e($page['title'] ?: $page['path']) ?></strong>
                <div class="timeline__text break"><?= V::e($page['path']) ?> · на экране <?= V::duration((int) $page['active_seconds']) ?><?= (int) $page['scroll'] ? ' · прочитано ' . (int) $page['scroll'] . '%' : '' ?></div>
                <?php foreach ($page['events'] as $e): ?>
                  <div class="timeline__text">→ <?= V::e(Visits::EVENT_LABELS[$e['name']] ?? $e['name']) ?><?= $e['name'] === 'scroll' ? ' ' . V::e($e['data']) . '%' : ($e['data'] !== null && !in_array($e['name'], ['lead', 'form_view', 'form_start'], true) ? ': ' . V::e($e['data']) : '') ?></div>
                <?php endforeach; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2>Комментарии и история</h2>
      <form method="post" action="<?= $action('comment') ?>">
        <?= V::csrf() ?>
        <label class="field"><span class="sr">Комментарий</span><textarea name="text" rows="3" maxlength="2000" required placeholder="Например: перезвонить в пятницу после 15:00"></textarea></label>
        <button class="btn btn--primary">Добавить комментарий</button>
      </form>
      <ol class="timeline">
        <?php foreach ($events as $e): ?>
          <li class="timeline__item">
            <time><?= V::date($e['created_at']) ?></time>
            <div>
              <strong><?= V::e(['created' => 'Поступила', 'status' => 'Статус', 'comment' => 'Комментарий'][$e['type']] ?? $e['type']) ?></strong>
              <span class="muted small">· <?= V::e($e['actor'] ?? '') ?></span>
              <?php if ($e['text'] !== null): ?><div class="timeline__text"><?= nl2br(V::e($e['text'])) ?></div><?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>

  <aside class="lead-side">
    <section class="panel">
      <h2>Путь клиента</h2>
      <?php if ($visits === []): ?>
        <p class="muted small">Других заходов на сайт нет.</p>
      <?php else: ?>
        <?php
          $first = $visits[0];
          $days = (int) floor((strtotime($lead['created_at']) - strtotime($first['started_at'])) / 86400);
        ?>
        <p class="small">Первый заход: <?= V::e($first['source']) ?>, <?= V::date($first['started_at']) ?>.
          <?= $days > 0 ? 'До заявки ' . $days . ' дн.' : 'Заявка в тот же день.' ?> Всего заходов: <?= count($visits) ?>.</p>
        <ul class="plain">
          <?php foreach (array_slice(array_reverse($visits), 0, 10) as $v): ?>
            <li class="small">
              <a href="/crm/visit/<?= $v['id'] ?>"><?= V::date($v['started_at']) ?></a> · <?= V::e($v['source']) ?> · <?= (int) $v['pages'] ?> стр., <?= V::duration((int) $v['active_seconds']) ?>
              <?= (int) $v['has_tel'] ? ' · ☎' : '' ?><?= $v['lead_id'] !== null && (int) $v['lead_id'] === $id ? ' · <strong>заявка</strong>' : '' ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <?php if ($samePhone !== []): ?>
      <section class="panel">
        <h2>Ещё заявки с этого номера</h2>
        <ul class="plain">
          <?php foreach ($samePhone as $s): ?>
            <li class="small"><a href="/crm/lead/<?= (int) $s['id'] ?>">№<?= (int) $s['id'] ?></a> · <?= V::date($s['created_at']) ?> · <?= V::e(Status::label($s['status'])) ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <?php if (($user['role'] ?? '') === 'manager'): ?>
      <section class="panel">
        <h2>Удалить</h2>
        <p class="muted small">По просьбе человека (отзыв согласия) или если это спам. Удаление нельзя отменить.</p>
        <form method="post" action="<?= $action('delete') ?>" data-confirm="Удалить заявку №<?= $id ?> без возможности восстановления?">
          <?= V::csrf() ?>
          <button class="btn btn--danger">Удалить заявку</button>
        </form>
      </section>
    <?php endif; ?>
  </aside>
</div>
