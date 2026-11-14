<?php $pname = esc(\Platform\Services\PlatformSettings::value('general', 'platformName', 'CheckA+')); ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>Sign in · <?= $pname ?> Platform</title>
<?= $this->include('Platform\Views\_styles') ?>
<style>body{display:grid;place-items:center;min-height:100vh;padding:16px;background:#0f1a22}.box{width:100%;max-width:380px}.logo{color:#fff;justify-content:center;margin-bottom:8px}</style>
</head><body>
<div class="box">
  <div class="logo" style="display:flex"><span class="mark">✓</span><span><?= $pname ?><small>Platform admin</small></span></div>
  <div class="card">
    <?php if ($m = session()->getFlashdata('error')): ?><div class="flash err"><?= esc($m) ?></div><?php endif; ?>
    <?php if ($m = session()->getFlashdata('success')): ?><div class="flash ok"><?= esc($m) ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('login') ?>">
      <?= csrf_field() ?>
      <div class="f"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" required autofocus autocomplete="username"></div>
      <div class="f"><label for="password">Password</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
      <button class="btn" style="width:100%;justify-content:center">Sign in</button>
    </form>
  </div>
</div>
</body></html>
