<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>Redirecting to <?= esc($gateway) ?>…</title>
<style>body{font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0;background:#f4f5f6;color:#2d2d2d}button{background:#37b34a;color:#fff;border:0;border-radius:10px;padding:12px 22px;font-weight:700;font-size:15px;cursor:pointer}</style></head>
<body><form id="f" method="post" action="<?= esc($action, 'attr') ?>" style="text-align:center">
<?php foreach ($fields as $k => $v): ?><input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc($v, 'attr') ?>"><?php endforeach; ?>
<p>Taking you to <?= esc($gateway) ?> to pay securely…</p><noscript><button>Continue</button></noscript></form>
<script>document.getElementById('f').submit()</script></body></html>
