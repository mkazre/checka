<?php
/** Shared <head> + header for public platform pages. @var string $title */
$site = rtrim(str_replace('://app.', '://', \Platform\Services\Billing::appUrl()), '/');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= esc($title ?? 'CheckA+') ?> — <?= esc(\Platform\Services\PlatformSettings::value('general', 'platformName', 'CheckA+')) ?></title>
<?= $extraHead ?? '' ?>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--g:#37b34a;--g2:#2a8f3a;--ink:#2d2d2d;--mut:#6b7178;--line:#e2e4e6;--bg:#f4f5f6}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.55 'Inter',system-ui,sans-serif}
a{color:var(--g2)}
.top{background:#fff;border-bottom:1px solid var(--line)}.top div{max-width:860px;margin:0 auto;padding:14px 20px;display:flex;justify-content:space-between;align-items:center}
.top img{height:30px;display:block}
.wrap{max-width:860px;margin:0 auto;padding:34px 20px 70px}
.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:28px;margin-bottom:18px}
h1{font-size:24px;font-weight:800;letter-spacing:-.01em;margin:0 0 6px}h2{font-size:17px;margin:0 0 12px}
.sub{color:var(--mut);margin:0 0 20px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:10px;padding:13px 22px;font:700 15px 'Inter',sans-serif;cursor:pointer;text-decoration:none;background:var(--g);color:#fff}
.btn:hover{background:var(--g2)}.btn.sec{background:#fff;color:var(--ink);border:1px solid #d6dce1}.btn.sec:hover{background:#f7f8f9}
.mono{font-family:'JetBrains Mono',monospace;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--mut)}
.err{background:#fde8e6;color:#9b1c13;border-radius:10px;padding:12px 14px;margin-bottom:16px}
.ok{background:#e6f6e9;color:#1f6b2c;border-radius:10px;padding:12px 14px;margin-bottom:16px}
table{width:100%;border-collapse:collapse}td,th{padding:9px 0;border-bottom:1px solid #eef0f2;text-align:left}td.r,th.r{text-align:right}
.tot td{font-weight:800;font-size:17px;border:0}
.methods{display:grid;gap:12px}
.method{display:flex;justify-content:space-between;align-items:center;gap:14px;border:1px solid var(--line);border-radius:12px;padding:16px 18px;background:#fff}
.method.rec{border:2px solid var(--g);background:#37b34a0d}
.method b{display:block}.method small{color:var(--mut)}
.spin{width:42px;height:42px;border:4px solid #e3f2e5;border-top-color:var(--g);border-radius:50%;margin:0 auto 16px;animation:s 1s linear infinite}@keyframes s{to{transform:rotate(360deg)}}
@media(max-width:600px){.card{padding:20px}.method{flex-direction:column;align-items:stretch}}
</style></head><body>
<div class="top"><div><a href="<?= esc($site, 'attr') ?>/"><img src="<?= esc($site, 'attr') ?>/assets/logo.png" alt="CheckA+"></a><a href="<?= esc($site, 'attr') ?>/login" style="font-weight:600;font-size:13.5px;color:#4a4a4a;text-decoration:none">Sign in</a></div></div>
<div class="wrap">
