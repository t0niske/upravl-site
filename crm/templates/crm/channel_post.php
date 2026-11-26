<?php

use App\Bot\Channel;
use App\Crm\View as V;

/** @var array $post */
$at = new DateTimeImmutable((string) $post['publish_at']);
?>
<a class="back" href="/crm/channel">← Канал MAX</a>
<div class="head"><h1><?= $post['id'] ? 'Пост №' . (int) $post['id'] : 'Новый пост' ?></h1></div>

<?php if (!empty($post['error'])): ?><div class="flash flash--error"><?= V::e($post['error']) ?></div><?php endif; ?>

<form method="post" action="/crm/channel/save" class="panel narrow">
  <?= V::csrf() ?>
  <input type="hidden" name="id" value="<?= V::e((string) $post['id']) ?>">
  <label class="field"><span>Название (видно только здесь)</span><input name="title" value="<?= V::e($post['title']) ?>" maxlength="150" placeholder="Миф: банкрота не возьмут на работу"></label>
  <div class="grid2">
    <label class="field"><span>Дата</span><input type="date" name="date" value="<?= $at->format('Y-m-d') ?>" required></label>
    <label class="field"><span>Время (Томск)</span><input type="time" name="time" value="<?= $at->format('H:i') ?>" required></label>
  </div>
  <label class="field"><span>Текст поста (до <?= Channel::MAX_TEXT ?> знаков; ссылки вставляйте как есть)</span>
    <textarea name="text" rows="16" maxlength="<?= Channel::MAX_TEXT ?>" required><?= V::e($post['text']) ?></textarea>
  </label>
  <label class="check"><input type="checkbox" name="pin"<?= (int) $post['pin'] ? ' checked' : '' ?>> Закрепить в канале после публикации</label>
  <label class="check"><input type="checkbox" name="approve"<?= $post['status'] === 'approved' ? ' checked' : '' ?>> Одобрено — опубликовать в указанное время</label>
  <div class="actions">
    <button class="btn btn--primary">Сохранить</button>
  </div>
</form>

<?php if ($post['id']): ?>
  <div class="actions">
    <form method="post" action="/crm/channel/<?= (int) $post['id'] ?>/publish" class="inline"><?= V::csrf() ?><button class="btn">Опубликовать сейчас</button></form>
    <form method="post" action="/crm/channel/<?= (int) $post['id'] ?>/delete" class="inline"><?= V::csrf() ?><button class="btn btn--danger">Удалить</button></form>
  </div>
<?php endif; ?>
