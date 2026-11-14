<div class="head"><div><h1>Settings</h1><p class="sub">Everything the platform does is configured here.</p></div></div>
<div class="tabs">
  <?php foreach ($tabs as $k => $l): ?><a href="<?= site_url('settings/' . $k) ?>" class="<?= $tab === $k ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?>
</div>
