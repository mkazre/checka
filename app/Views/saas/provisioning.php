<?php /** Shown while a new workspace is still being set up. @var array $tenant */ ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="15"><title>Setting up your workspace…</title>
<style>
  body{margin:0;background:#f4f5f6;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#2d2d2d;display:grid;place-items:center;min-height:100vh;padding:20px}
  .card{background:#fff;border:1px solid #e2e4e6;border-radius:16px;max-width:440px;width:100%;padding:32px;text-align:center;box-shadow:0 18px 50px rgba(16,23,29,.08)}
  .spin{width:44px;height:44px;border:4px solid #e3f0ea;border-top-color:#0b7a5a;border-radius:50%;margin:0 auto 18px;animation:s 1s linear infinite}
  @keyframes s{to{transform:rotate(360deg)}}
  h1{font-size:20px;margin:0 0 10px}p{color:#5b6570;line-height:1.6;font-size:14.5px;margin:0}
</style></head>
<body><div class="card">
  <div class="spin"></div>
  <h1>Setting up <?= esc($tenant['name'] ?? 'your workspace') ?></h1>
  <p>We're creating your workspace, checklists and admin login. This usually takes under a minute — this page refreshes by itself.</p>
</div></body></html>
