<?php
/** Shown instead of the app when a workspace is suspended or closed. @var array $tenant @var string $status */
$name   = esc($tenant['name'] ?? 'This workspace');
$closed = in_array($status, ['cancelled', 'closed'], true);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $closed ? 'Workspace closed' : 'Workspace suspended' ?></title>
<style>
  body{margin:0;background:#f4f5f6;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#2d2d2d;display:grid;place-items:center;min-height:100vh;padding:20px}
  .card{background:#fff;border:1px solid #e2e4e6;border-radius:16px;max-width:460px;width:100%;padding:30px;text-align:center;box-shadow:0 18px 50px rgba(16,23,29,.08)}
  img{height:52px;margin-bottom:10px}
  h1{font-size:21px;margin:6px 0 10px}
  p{color:#5b6570;line-height:1.6;font-size:14.5px}
  .btn{display:inline-block;margin-top:14px;background:<?= esc(brand('primary')) ?>;color:#fff;text-decoration:none;font-weight:700;padding:13px 24px;border-radius:10px}
  .muted{font-size:12.5px;color:#8a95a1;margin-top:16px}
</style><link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/icon-32.png"><link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
</head>
<body><div class="card">
  <img src="<?= esc(brand('logo')) ?>" alt="">
  <?php if ($closed): ?>
    <h1>This workspace is closed</h1>
    <p><?= $name ?>'s CheckA+ subscription has ended. Your data is kept for a limited time — contact us to reactivate or request an export.</p>
  <?php else: ?>
    <h1>Your workspace is paused</h1>
    <p><?= $name ?>'s subscription has an unpaid balance, so access is paused. <b>Nothing has been deleted.</b> As soon as payment is received, everything switches back on automatically.</p>
    <a class="btn" href="<?= site_url('subscription') ?>">View &amp; pay subscription</a>
  <?php endif; ?>
  <div class="muted">Already paid? It can take a few minutes to reactivate. <a href="<?= site_url('logout') ?>">Sign out</a></div>
</div></body></html>
