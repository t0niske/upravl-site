<?php

use App\Crm\Staff;
use App\Crm\View as V;

/** @var list<array> $staff */
?>
<div class="head"><h1>Сотрудники</h1></div>
<p class="muted">Администратор работает с заявками. Руководитель — всё то же, плюс отчёты, выгрузка и удаление заявок, сотрудники и журнал действий.</p>

<div class="staff-list">
  <?php foreach ($staff as $s): ?>
    <details class="panel staff<?= (int) $s['active'] ? '' : ' is-off' ?>">
      <summary>
        <span class="strong"><?= V::e($s['name']) ?></span>
        <span class="muted"><?= V::e($s['login']) ?> · <?= V::e(Staff::ROLES[$s['role']] ?? $s['role']) ?><?= (int) $s['active'] ? '' : ' · отключён' ?></span>
        <span class="muted small">вход: <?= V::date($s['last_login_at']) ?></span>
      </summary>
      <form method="post" action="/crm/staff" class="grid2">
        <?= V::csrf() ?>
        <input type="hidden" name="id" value="<?= $s['id'] ?>">
        <label class="field"><span>Имя</span><input name="name" value="<?= V::e($s['name']) ?>" required maxlength="100"></label>
        <label class="field"><span>Роль</span>
          <select name="role">
            <?php foreach (Staff::ROLES as $key => $label): ?>
              <option value="<?= $key ?>"<?= $s['role'] === $key ? ' selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span>Новый пароль</span><input name="password" type="password" autocomplete="new-password" placeholder="не менять" minlength="<?= Staff::MIN_PASSWORD ?>"></label>
        <div class="field">
          <label class="check"><input type="checkbox" name="active"<?= (int) $s['active'] ? ' checked' : '' ?>> Доступ открыт</label>
        </div>
        <div class="span2"><button class="btn btn--primary">Сохранить</button></div>
      </form>
    </details>
  <?php endforeach; ?>
</div>

<section class="panel narrow">
  <h2>Добавить сотрудника</h2>
  <form method="post" action="/crm/staff">
    <?= V::csrf() ?>
    <div class="grid2">
      <label class="field"><span>Имя</span><input name="name" required maxlength="100" placeholder="Юрий"></label>
      <label class="field"><span>Логин</span><input name="login" required pattern="[A-Za-z0-9._\-]{3,32}" autocapitalize="none" placeholder="yuriy"></label>
      <label class="field"><span>Пароль</span><input name="password" type="password" required minlength="<?= Staff::MIN_PASSWORD ?>" autocomplete="new-password"></label>
      <label class="field"><span>Роль</span>
        <select name="role"><?php foreach (Staff::ROLES as $key => $label): ?><option value="<?= $key ?>"><?= $label ?></option><?php endforeach; ?></select>
      </label>
    </div>
    <button class="btn btn--primary">Добавить</button>
  </form>
</section>
