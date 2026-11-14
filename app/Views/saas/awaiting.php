<?php /** Workspace address reserved, first payment not received yet. @var array $tenant @var string|null $payUrl */ ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Complete your payment</title>
<style>
  body{margin:0;background:#f4f5f6;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#2d2d2d;display:grid;place-items:center;min-height:100vh;padding:20px}
  .card{background:#fff;border:1px solid #e2e4e6;border-radius:16px;max-width:440px;width:100%;padding:32px;text-align:center;box-shadow:0 18px 50px rgba(16,23,29,.08)}
  h1{font-size:20px;margin:0 0 10px}p{color:#5b6570;line-height:1.6;font-size:14.5px}
  .btn{display:inline-block;margin-top:10px;background:#37b34a;color:#fff;text-decoration:none;font-weight:700;padding:13px 24px;border-radius:10px}
</style></head>
<body><div class="card">
  <h1><?= esc($tenant['name'] ?? 'Your workspace') ?> is almost ready</h1>
  <p>This address is reserved for you. As soon as your first payment is received, we'll set up your workspace automatically.</p>
  <?php if ($payUrl): ?><a class="btn" href="<?= esc($payUrl, 'attr') ?>">Complete payment</a><?php endif; ?>
</div></body></html>
