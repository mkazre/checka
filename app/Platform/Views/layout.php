<?php
/** Platform admin panel shell. Sections: title, content. */
$admin = pf_admin();
$path  = trim(uri_string(), '/');
$first = explode('/', $path)[0];
$nav   = [
    ''         => ['Dashboard', 'M3 12l9-8 9 8v8a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z'],
    'tenants'  => ['Workspaces', 'M4 5h16v4H4zM4 11h16v4H4zM4 17h10v3H4z'],
    'packages' => ['Packages', 'M12 2l9 5v10l-9 5-9-5V7zM12 12l9-5M12 12v10M12 12L3 7'],
    'invoices' => ['Invoices', 'M6 2h9l5 5v15H6zM14 2v6h6M9 13h6M9 17h6'],
    'leads'    => ['Leads', 'M4 4h16v12H5.2L4 17.2zM8 9h8M8 12h5'],
    'clients'  => ['All clients', 'M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 21a8 8 0 0116 0'],
    'settings' => ['Settings', 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-2.9 1.2V21a2 2 0 11-4 0v-.1A1.7 1.7 0 009 19.4a1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.7 1.7 0 004.6 15H4.5a2 2 0 110-4h.1A1.7 1.7 0 006 9.6a1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H10a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z'],
];
if (($admin['role'] ?? '') === 'owner') {
    $nav['admins'] = ['Team', 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8'];
}
$pname = esc(\Platform\Services\PlatformSettings::value('general', 'platformName', 'CheckA+'));
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php $this->renderSection('title') ?> ·<?= $pname ?> Platform</title>
<?= $this->include('Platform\Views\_styles') ?>
</head>
<body>
<div class="shell">
  <aside class="side" id="side">
    <a class="logo" href="<?= site_url('/') ?>"><span class="mark">✓</span><span><?= $pname ?><small>Platform</small></span></a>
    <nav>
      <?php foreach ($nav as $key => [$label, $icon]): ?>
        <a href="<?= site_url($key) ?>" class="<?= $first === $key ? 'on' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $icon ?>"/></svg><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="me">
      <a href="<?= site_url('account') ?>"><b><?= esc($admin['name'] ?? '') ?></b><small><?= esc($admin['email'] ?? '') ?></small></a>
      <a class="out" href="<?= site_url('logout') ?>">Sign out</a>
    </div>
  </aside>
  <main>
    <button class="burger" onclick="document.getElementById('side').classList.toggle('open')" aria-label="Menu">☰</button>
    <?php foreach (['success' => 'ok', 'error' => 'err', 'info' => 'info'] as $k => $cls): ?>
      <?php if ($m = session()->getFlashdata($k)): ?><div class="flash <?= $cls ?>"><?= esc($m) ?></div><?php endif; ?>
    <?php endforeach; ?>
    <?php if ($errs = session()->getFlashdata('errors')): ?>
      <div class="flash err"><?php foreach ((array) $errs as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?= $this->renderSection('content') ?>
  </main>
</div>
<script>
document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
</script>
</body></html>
