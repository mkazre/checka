<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · CheckA+ by WheelsTurn</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  :root{--brand:#37b34a;--brand-ink:#2a8f3a;--ink:#2d2d2d;--ink-3:#7a7a7a;--line:#e2e4e6}
  *{box-sizing:border-box}
  html,body{margin:0;height:100%}
  body{
    font-family:'Inter',system-ui,sans-serif;color:var(--ink);
    min-height:100vh;display:grid;place-items:center;padding:24px;
    background:linear-gradient(rgba(45,45,45,.55),rgba(45,45,45,.65)),
               url('<?= base_url('assets/img/login.jpg') ?>') center/cover no-repeat fixed;
  }
  .loginbox{
    background:#fff;width:100%;max-width:400px;border-radius:18px;
    padding:38px 34px 30px;box-shadow:0 24px 60px rgba(0,0,0,.35);
    animation:rise .35s ease;
  }
  @keyframes rise{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
  .loginbox .logo{display:block;height:74px;width:auto;margin:0 auto 18px}
  .loginbox h1{font-size:22px;font-weight:800;text-align:center;margin:0;letter-spacing:-.01em}
  .loginbox .sub{text-align:center;color:var(--ink-3);font-size:13.5px;margin:4px 0 22px}
  label.fld{display:block;margin-bottom:14px}
  label.fld > span{display:block;font-size:12px;font-weight:600;color:var(--ink-3);margin-bottom:6px}
  input[type=email],input[type=password]{
    width:100%;border:1px solid var(--line);border-radius:11px;padding:12px 13px;font-size:15px;
    font-family:inherit;color:var(--ink);background:#fff;transition:border-color .15s,box-shadow .15s}
  input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px #37b34a2e}
  .remember{display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--ink-3);margin:2px 0 20px;font-weight:500}
  .remember input{width:16px;height:16px;accent-color:var(--brand)}
  .btn{width:100%;border:none;background:var(--brand);color:#fff;font-family:inherit;font-weight:700;font-size:15px;
    padding:13px;border-radius:11px;cursor:pointer;transition:filter .15s}
  .btn:hover{filter:brightness(1.06)}
  .alert{background:#fbe9e7;border:1px solid #f0b3ab;color:#a1281c;border-radius:11px;padding:10px 13px;font-size:13.5px;margin-bottom:18px}
  .alert div+div{margin-top:3px}
  .foot{text-align:center;color:#9aa0a6;font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;margin-top:22px}
  @media(max-width:420px){.loginbox{padding:30px 22px 24px;border-radius:14px}.loginbox .logo{height:60px}}
</style>
</head>
<body>
  <div class="loginbox">
    <img class="logo" src="<?= base_url('assets/img/logo.png') ?>" alt="CheckA+ by WheelsTurn">
    <h1>Welcome back</h1>
    <div class="sub">Sign in to the workshop portal</div>

    <?php if (session('error') !== null) : ?>
      <div class="alert"><?= esc(session('error')) ?></div>
    <?php elseif (session('errors') !== null) : ?>
      <div class="alert">
        <?php if (is_array(session('errors'))) : ?>
          <?php foreach (session('errors') as $e) : ?><div><?= esc($e) ?></div><?php endforeach ?>
        <?php else : ?>
          <div><?= esc(session('errors')) ?></div>
        <?php endif ?>
      </div>
    <?php endif ?>

    <form action="<?= url_to('login') ?>" method="post">
      <?= csrf_field() ?>
      <label class="fld">
        <span>Email address</span>
        <input type="email" name="email" inputmode="email" autocomplete="email" value="<?= old('email') ?>" required autofocus>
      </label>
      <label class="fld">
        <span>Password</span>
        <input type="password" name="password" autocomplete="current-password" required>
      </label>
      <label class="remember">
        <input type="checkbox" name="remember" <?= old('remember') ? 'checked' : '' ?>> Keep me signed in
      </label>
      <button type="submit" class="btn">Sign in</button>
    </form>

    <div class="foot">CheckA+ · by WheelsTurn</div>
  </div>
</body>
</html>
