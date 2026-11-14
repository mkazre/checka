<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Exception · Checka (dev)</title>
<style>
  body{margin:0;background:#12171d;color:#e9eef2;font-family:ui-monospace,'JetBrains Mono',monospace;font-size:13px;padding:24px}
  h1{font-family:system-ui,sans-serif;font-size:20px;color:#e9a29c}
  .meta{color:#8395a6;margin:6px 0 18px}
  pre{background:#1e2731;border:1px solid #2b3742;border-radius:10px;padding:16px;overflow:auto;line-height:1.6}
  .file{color:#8fd3a7}
</style>
<link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/icon-32.png"><link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
</head>
<body>
  <h1><?= esc($exception->getMessage() ?: get_class($exception)) ?></h1>
  <div class="meta">
    <span class="file"><?= esc(clean_path($exception->getFile())) ?></span> : <?= (int) $exception->getLine() ?>
  </div>
  <pre><?= esc($exception->getTraceAsString()) ?></pre>
</body>
</html>
