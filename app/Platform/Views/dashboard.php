<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="head">
  <div><h1>Dashboard</h1><p class="sub">Your customers, revenue and anything that needs attention.</p></div>
  <div class="row-actions"><a class="btn" href="<?= site_url('tenants/new') ?>">+ New workspace</a></div>
</div>

<?php if (! $hasPkgs): ?>
  <div class="flash info">Start by creating your first package under <a href="<?= site_url('packages/new') ?>">Packages</a>. You choose its name, price, modules and limits.</div>
<?php endif; ?>

<div class="grid g4" style="margin-bottom:18px">
  <div class="stat"><b><?= pf_money($mrr) ?></b><span>Monthly recurring revenue</span></div>
  <div class="stat"><b><?= $byStatus['active'] + $byStatus['past_due'] ?></b><span>Paying workspaces</span></div>
  <div class="stat"><b><?= $byStatus['trial'] ?></b><span>On trial</span></div>
  <div class="stat"><b><?= $new30 ?></b><span>New in the last 30 days</span></div>
</div>

<div class="grid g2">
  <div>
    <div class="card">
      <h2>Workspaces by status <small><?= $total ?> in total</small></h2>
      <?php foreach ($byStatus as $s => $n): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0">
          <a href="<?= site_url('tenants?status=' . $s) ?>" style="text-decoration:none"><?= pf_status($s) ?></a><b><?= $n ?></b>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <h2>Workspaces per package</h2>
      <?php if (! $perPkg): ?><p class="muted">No workspaces yet.</p><?php endif; ?>
      <?php $max = max($perPkg ?: [1]); foreach ($perPkg as $name => $n): ?>
        <div style="margin-bottom:10px"><div style="display:flex;justify-content:space-between"><span><?= esc($name) ?></span><b><?= $n ?></b></div>
        <div class="bar"><i style="width:<?= round($n / $max * 100) ?>%"></i></div></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div>
    <?php if ($failed || $pending): ?>
      <div class="card" style="border-color:#f3c9c4">
        <h2>Needs attention</h2>
        <?php foreach ($failed as $t): ?>
          <div class="ev"><span class="badge s-suspended">Setup failed</span><a href="<?= site_url('tenants/' . $t['id']) ?>"><?= esc($t['company_name']) ?></a></div>
        <?php endforeach; ?>
        <?php foreach ($pending as $t): ?>
          <div class="ev"><?= pf_status('provisioning') ?><a href="<?= site_url('tenants/' . $t['id']) ?>"><?= esc($t['company_name']) ?></a><span class="muted small">waiting for the setup job</span></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="card">
      <h2>Trials ending in the next 7 days</h2>
      <?php if (! $soon): ?><p class="muted">None.</p><?php endif; ?>
      <?php foreach ($soon as $t): ?>
        <div class="ev"><time><?= pf_date($t['trial_ends_at']) ?></time><a href="<?= site_url('tenants/' . $t['id']) ?>"><?= esc($t['company_name']) ?></a><span class="muted"><?= esc($t['package_name'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <h2>Recent activity</h2>
      <?php if (! $events): ?><p class="muted">Nothing yet.</p><?php endif; ?>
      <?php foreach ($events as $e): ?>
        <div class="ev"><time><?= pf_date($e['created_at'], true) ?></time>
          <div><?php if ($e['tenant_id']): ?><a href="<?= site_url('tenants/' . $e['tenant_id']) ?>"><?= esc($e['company_name'] ?? '#' . $e['tenant_id']) ?></a> · <?php endif; ?><?= esc($e['message']) ?>
          <span class="muted small">— <?= esc($e['actor'] ?: 'system') ?></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
