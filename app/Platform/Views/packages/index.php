<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Packages<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="head">
  <div><h1>Packages</h1><p class="sub">Everything here is yours to define: names, prices, trial length, modules and limits. Changes apply to existing workspaces immediately.</p></div>
  <a class="btn" href="<?= site_url('packages/new') ?>">+ New package</a>
</div>

<?php if (! $packages): ?>
  <div class="card empty">No packages yet. <a href="<?= site_url('packages/new') ?>">Create your first one</a>.</div>
<?php endif; ?>

<div class="grid g3">
<?php foreach ($packages as $p): $mods = \Platform\Models\PackageModel::modules($p); $lim = \Platform\Models\PackageModel::limits($p); ?>
  <div class="card" style="<?= $p['highlight'] ? 'border:2px solid var(--pri)' : '' ?><?= $p['is_active'] ? '' : ';opacity:.65' ?>">
    <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start">
      <div><h2 style="margin:0"><?= esc($p['name']) ?></h2><div class="muted small"><?= esc($p['tagline']) ?></div></div>
      <div class="nowrap">
        <?php if ($p['highlight']): ?><span class="badge s-active">Featured</span><?php endif; ?>
        <?php if (! $p['is_active']): ?><span class="badge">Retired</span><?php elseif (! $p['is_public']): ?><span class="badge">Private</span><?php endif; ?>
      </div>
    </div>
    <div style="margin:14px 0 4px;font-size:22px;font-weight:800"><?= pf_money($p['price_monthly']) ?><span class="muted small" style="font-weight:400"> / month</span></div>
    <div class="muted small"><?= pf_money($p['price_annual']) ?> / year · <?= (int) $p['trial_days'] ?>-day trial · <b><?= $counts[$p['id']] ?? 0 ?></b> workspace(s)</div>
    <div style="margin:12px 0">
      <?php foreach ($mods as $k): if (! isset($catalog[$k])) continue; ?>
        <span class="tag <?= $catalog[$k]['status'] === 'roadmap' ? 'road' : '' ?>"><?= esc($catalog[$k]['name']) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="small muted" style="margin-bottom:14px">
      <?php foreach ($limitDef as $k => $d): ?>
        <?= esc($d['name']) ?>: <b><?= isset($lim[$k]) && $lim[$k] !== null ? number_format($lim[$k]) : 'Unlimited' ?></b><br>
      <?php endforeach; ?>
    </div>
    <div class="row-actions">
      <a class="btn sm" href="<?= site_url('packages/' . $p['id'] . '/edit') ?>">Edit</a>
      <a class="btn sm sec" href="<?= site_url('packages/' . $p['id'] . '/copy') ?>">Duplicate</a>
      <form method="post" action="<?= site_url('packages/' . $p['id'] . '/delete') ?>" data-confirm="Delete package &quot;<?= esc($p['name'], 'attr') ?>&quot;? If workspaces use it, it will be retired instead.">
        <?= csrf_field() ?><button class="btn sm sec">Delete</button></form>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?= $this->endSection() ?>
