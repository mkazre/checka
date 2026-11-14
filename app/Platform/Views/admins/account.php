<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>My account<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="head"><div><h1>My account</h1><p class="sub"><?= esc($me['email']) ?> · <?= $me['role'] === 'owner' ? 'Owner' : 'Staff' ?></p></div></div>
<form class="card" method="post" action="<?= site_url('account') ?>" style="max-width:520px">
  <?= csrf_field() ?>
  <div class="f"><label>Name</label><input type="text" name="name" value="<?= esc($me['name']) ?>"></div>
  <h2 style="margin-top:20px">Change password</h2>
  <div class="f"><label>Current password</label><input type="password" name="current" autocomplete="current-password"></div>
  <div class="f"><label>New password</label><input type="password" name="password" minlength="10" autocomplete="new-password"><small>Leave empty to keep your current password.</small></div>
  <button class="btn">Save</button>
</form>
<?= $this->endSection() ?>
