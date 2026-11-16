<?php /** Shown while a new workspace is still being set up. @var array $tenant */ ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setting up your workspace…</title>
<style>
  body{margin:0;background:#f4f5f6;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#2d2d2d;display:grid;place-items:center;min-height:100vh;padding:20px}
  .card{background:#fff;border:1px solid #e2e4e6;border-radius:16px;max-width:560px;width:100%;padding:32px;text-align:center;box-shadow:0 18px 50px rgba(16,23,29,.08)}
  .spin{width:44px;height:44px;border:4px solid #e3f0ea;border-top-color:#37b34a;border-radius:50%;margin:0 auto 18px;animation:s 1s linear infinite}
  @keyframes s{to{transform:rotate(360deg)}}
  h1{font-size:20px;margin:0 0 10px}p{color:#5b6570;line-height:1.6;font-size:14.5px;margin:0}
</style><link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/icon-32.png"><link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
</head>
<body><div class="card">
  <div class="spin"></div>
  <h1>Setting up <?= esc($tenant['name'] ?? 'your workspace') ?></h1>
  <p>We're creating your workspace, checklists and admin login. This usually takes under a minute — you'll be taken in as soon as it's ready.</p>
  <?= view('saas/_did_you_know') ?>
</div>
<script>setInterval(function(){fetch(location.href,{cache:'no-store'}).then(function(r){if(r.status!==503)location.reload()}).catch(function(){})},7000);</script></body></html>
