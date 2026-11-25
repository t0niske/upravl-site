<?php

use App\Crm\Staff;
use App\Crm\View as V;

/** @var array $user */
?>
<div class="head"><h1><?= V::e($user['name']) ?></h1></div>

<section class="panel narrow">
  <h2>Сменить пароль</h2>
  <form method="post" action="/crm/profile">
    <?= V::csrf() ?>
    <label class="field"><span>Текущий пароль</span><input name="current" type="password" autocomplete="current-password" required></label>
    <label class="field"><span>Новый пароль</span><input name="password" type="password" autocomplete="new-password" minlength="<?= Staff::MIN_PASSWORD ?>" required></label>
    <label class="field"><span>Ещё раз</span><input name="password2" type="password" autocomplete="new-password" minlength="<?= Staff::MIN_PASSWORD ?>" required></label>
    <button class="btn btn--primary">Сменить</button>
  </form>
</section>
