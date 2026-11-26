<?php

use App\Bot\Channel;
use App\Crm\View as V;

/** @var ?array $channel @var list<array> $upcoming @var list<array> $published */
$badge = ['draft' => 'no_answer', 'approved' => 'in_work', 'published' => 'contract', 'failed' => 'refused'];
$snippet = static fn (string $t): string => mb_strimwidth(preg_replace('/\s+/u', ' ', $t) ?? '', 0, 140, '…');
?>
<div class="head">
  <h1>Канал MAX</h1>
  <div class="head__actions"><a class="btn btn--primary" href="/crm/channel/new">Новый пост</a></div>
</div>

<?php if ($channel === null): ?>
  <div class="panel robot-alert">
    <p class="strong">Бот ещё не подключён к каналу.</p>
    <p>Откройте канал в MAX → Настройки → Администраторы → добавьте бота «Жить без Долгов реально» с правом публиковать сообщения. Бот запомнит канал сам, обновите эту страницу.</p>
  </div>
<?php else: ?>
  <p class="muted">Канал<?= $channel['title'] !== '' ? ' «' . V::e($channel['title']) . '»' : '' ?> подключён. Бот публикует только одобренные посты, когда наступает их время (проверка раз в минуту).</p>
<?php endif; ?>

<h2 class="section-title">Запланировано <span class="muted"><?= count($upcoming) ?></span></h2>
<?php if ($upcoming === []): ?>
  <p class="empty">Нет запланированных постов.</p>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Когда</th><th>Пост</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($upcoming as $p): ?>
        <tr>
          <td class="nowrap"><?= V::date($p['publish_at']) ?></td>
          <td>
            <a href="/crm/channel/<?= $p['id'] ?>" class="strong"><?= V::e($p['title'] ?: 'Без названия') ?></a><?= (int) $p['pin'] ? ' <span class="tag">закрепить</span>' : '' ?>
            <div class="muted small"><?= V::e($snippet((string) $p['text'])) ?></div>
            <?php if ($p['error']): ?><div class="error small"><?= V::e($p['error']) ?></div><?php endif; ?>
          </td>
          <td><span class="badge badge--<?= $badge[$p['status']] ?? 'new' ?>"><?= V::e(Channel::STATUSES[$p['status']] ?? $p['status']) ?></span></td>
          <td class="nowrap">
            <form method="post" action="/crm/channel/<?= $p['id'] ?>/<?= $p['status'] === 'approved' ? 'draft' : 'approve' ?>" class="inline">
              <?= V::csrf() ?><button class="btn btn--sm<?= $p['status'] === 'approved' ? '' : ' btn--ok' ?>"><?= $p['status'] === 'approved' ? 'Снять' : 'Одобрить' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<h2 class="section-title">Опубликовано <span class="muted"><?= count($published) ?></span></h2>
<?php if ($published === []): ?>
  <p class="empty">Пока ничего не опубликовано.</p>
<?php else: ?>
  <div class="table-wrap">
    <table class="table table--compact">
      <tbody>
      <?php foreach ($published as $p): ?>
        <tr>
          <td class="nowrap"><?= V::date($p['published_at']) ?></td>
          <td><span class="strong"><?= V::e($p['title'] ?: 'Без названия') ?></span> <span class="muted small"><?= V::e($snippet((string) $p['text'])) ?></span>
            <?php if ($p['error']): ?><div class="error small"><?= V::e($p['error']) ?></div><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
