<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? 'Checka') ?> · Checka by WheelsTurn</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<?= $this->include('partials/theme') ?>
<?= $this->renderSection('head') ?>
</head>
<body>
<?php $isAdmin = auth()->loggedIn() && auth()->user()->inGroup('admin'); ?>
<?php $uri = uri_string(); ?>
<header class="topbar">
  <div class="wrap">
    <a class="logo" href="<?= site_url('dashboard') ?>">
      <span class="mark"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7">
        <circle cx="12" cy="12" r="9"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/><circle cx="12" cy="12" r="3.2"/></svg></span>
      <span><b>Checka</b><small>by WheelsTurn</small></span>
    </a>
    <nav class="nav">
      <a href="<?= site_url('dashboard') ?>" class="<?= str_starts_with($uri,'dashboard')?'active':'' ?>">Dashboard</a>
      <a href="<?= site_url('vehicles') ?>" class="<?= str_starts_with($uri,'vehicles')?'active':'' ?>">Vehicles</a>
      <?php if ($isAdmin): ?>
        <a href="<?= site_url('templates') ?>" class="<?= str_starts_with($uri,'templates')?'active':'' ?>">Templates</a>
        <a href="<?= site_url('users') ?>" class="<?= str_starts_with($uri,'users')?'active':'' ?>">Users</a>
      <?php endif; ?>
    </nav>
    <div class="right">
      <a class="btn sm" href="<?= site_url('inspections/new') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> New Inspection
      </a>
      <?php if (auth()->loggedIn()): ?>
        <div class="who"><b><?= esc(auth()->user()->username ?? auth()->user()->email) ?></b><br><?= $isAdmin?'Administrator':'Mechanic' ?></div>
        <a class="btn ghost sm" style="color:#cdd7e0;border-color:#33414d" href="<?= site_url('logout') ?>">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</header>

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
</body>
</html>
