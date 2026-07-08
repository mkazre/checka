<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? 'CheckA+') ?> · CheckA+ by WheelsTurn</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<?= $this->include('partials/theme') ?>
<?php if (feature('offline')): ?>
<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
<meta name="theme-color" content="#37b34a">
<link rel="apple-touch-icon" href="<?= base_url('assets/img/logo.png') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="CheckA+">
<?php endif; ?>
<?= $this->renderSection('head') ?>
</head>
<body>
<?php $isAdmin = auth()->loggedIn() && auth()->user()->inGroup('admin'); ?>
<?php $uri = uri_string(); ?>
<?php
  $h = (int) date('H');
  $greet = $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');
  $me = auth()->loggedIn() ? (auth()->user()->username ?: explode('@', (string) auth()->user()->email)[0]) : '';
?>
<header class="topbar" id="topbar">
  <div class="wrap">
    <a class="logo" href="<?= site_url('dashboard') ?>">
      <img src="<?= base_url('assets/img/logo.png') ?>" alt="CheckA+ by WheelsTurn">
    </a>
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <div class="topnav" id="topnav">
      <nav class="nav">
        <a href="<?= site_url('dashboard') ?>" class="<?= str_starts_with($uri,'dashboard')?'active':'' ?>">Dashboard</a>
        <a href="<?= site_url('vehicles') ?>" class="<?= str_starts_with($uri,'vehicles')?'active':'' ?>">Vehicles</a>
        <?php if ($isAdmin): ?>
          <a href="<?= site_url('templates') ?>" class="<?= str_starts_with($uri,'templates')?'active':'' ?>">Templates</a>
          <a href="<?= site_url('users') ?>" class="<?= str_starts_with($uri,'users')?'active':'' ?>">Users</a>
          <a href="<?= site_url('settings') ?>" class="<?= str_starts_with($uri,'settings')?'active':'' ?>">Settings</a>
        <?php endif; ?>
      </nav>
      <div class="right">
        <?php if (feature('offline')): ?>
        <button class="btn ghost sm" id="installBtn" type="button" style="display:none">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg> Install
        </button>
        <?php endif; ?>
        <a class="btn sm" href="<?= site_url('inspections/new') ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> New Inspection
        </a>
        <?php if (auth()->loggedIn()): ?>
          <div class="who"><b><?= esc($greet) ?>, <?= esc($me) ?></b><br><?= $isAdmin?'Administrator':'Mechanic' ?></div>
          <a class="btn ghost sm" href="<?= site_url('logout') ?>">Logout</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>
<script>
  (function(){ var t=document.getElementById('menuToggle'); if(t) t.addEventListener('click',function(){ document.getElementById('topbar').classList.toggle('open'); }); })();
</script>

<main class="container">
  <?php if (session()->getFlashdata('message')): ?>
    <div class="flash ok"><?= esc(session()->getFlashdata('message')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="flash err"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('errors')): ?>
    <div class="flash err"><?php foreach ((array) session()->getFlashdata('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?></div>
  <?php endif; ?>

  <?= $this->renderSection('content') ?>
</main>

<?= $this->renderSection('scripts') ?>
<?php if (feature('offline')): ?>
<script>
  window.CHECKA_BASE = "<?= rtrim(base_url(), '/') ?>/";
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(CHECKA_BASE + 'sw.js').catch(function(){});
  }
  // Install prompt (Android/desktop Chrome). iOS: Share → Add to Home Screen.
  var deferredPrompt;
  window.addEventListener('beforeinstallprompt', function(e){
    e.preventDefault(); deferredPrompt = e;
    var b = document.getElementById('installBtn'); if (b) b.style.display = 'inline-flex';
  });
  var ib = document.getElementById('installBtn');
  if (ib) ib.addEventListener('click', function(){
    if (!deferredPrompt) { alert('To install: use your browser menu → “Add to Home Screen”.'); return; }
    deferredPrompt.prompt(); deferredPrompt = null; ib.style.display = 'none';
  });
</script>
<script src="<?= base_url('assets/js/checka-offline.js') ?>" defer></script>
<?php else: ?>
<script>
  // Feature off — remove any previously installed service worker so the app is fully online-only again.
  if ('serviceWorker' in navigator) { navigator.serviceWorker.getRegistrations().then(function(rs){ rs.forEach(function(r){ r.unregister(); }); }); }
</script>
<?php endif; ?>
</body>
</html>
