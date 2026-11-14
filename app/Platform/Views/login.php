<?php $pname = esc(\Platform\Services\PlatformSettings::value('general', 'platformName', 'CheckA+')); ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>Sign in · <?= $pname ?> Platform</title>
<?= $this->include('Platform\Views\_styles') ?>
<style>body{display:grid;place-items:center;min-height:100vh;padding:16px;background:#fff}.box{width:100%;max-width:380px}.brand{text-align:center;margin-bottom:22px}.brand img{height:64px;width:auto}.brand small{display:block;margin-top:8px;font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--mut)}.box .card{box-shadow:0 18px 50px rgba(16,23,29,.08)}</style>
</head><body>
<div class="box">
  <div class="brand"><img src="<?= base_url('assets/platform/logo.png') ?>" alt="<?= $pname ?>"><small>Platform admin</small></div>
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
