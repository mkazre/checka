<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Something went wrong · Checka</title>
<style>
  body{margin:0;background:#eef1f3;font-family:system-ui,Inter,sans-serif;color:#12171d;display:grid;place-items:center;min-height:100vh;padding:20px;text-align:center}
  .box{background:#fff;border:1px solid #d6dce1;border-radius:16px;padding:40px;max-width:440px}
  h1{font-size:22px;margin:0 0 8px}
  p{color:#6b7682}
  a{color:#0d5e6e;font-weight:600;text-decoration:none}
</style>
</head>
<body>
  <div class="box">
    <h1>Something went wrong</h1>
    <p>We hit an unexpected error. The workshop team has been notified. Please try again.</p>
    <p><a href="<?= function_exists('site_url') ? site_url('dashboard') : '/' ?>">← Back to dashboard</a></p>
  </div>
</body>
</html>
