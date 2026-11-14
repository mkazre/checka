<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Workspaces<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $qs = http_build_query(array_filter($f)); ?>
<div class="head">
  <div><h1>Workspaces</h1><p class="sub">Every customer company and its own address on <?= esc(pf_root()) ?>.</p></div>
  <div class="row-actions">
    <a class="btn sec" href="<?= site_url('tenants/export') . ($qs ? '?' . $qs : '') ?>">Export CSV</a>
    <a class="btn" href="<?= site_url('tenants/new') ?>">+ New workspace</a>
  </div>
</div>

<form class="filters" method="get">
  <input type="text" name="q" value="<?= esc($f['q']) ?>" placeholder="Search company, address, email…">
  <select name="status" onchange="this.form.submit()">
    <option value="">All statuses</option>
    <?php foreach (\Platform\Models\TenantModel::STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
  </select>
  <select name="package" onchange="this.form.submit()">
    <option value="">All packages</option>
    <?php foreach ($packages as $p): ?><option value="<?= $p['id'] ?>" <?= (int) $f['package'] === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option><?php endforeach; ?>
  </select>
  <button class="btn sec">Search</button>
</form>

<div class="tbl">
<table>
  <thead><tr><th>Company</th><th>Address</th><th>Package</th><th>Status</th><th>Trial ends / paid until</th><th>Created</th></tr></thead>
  <tbody>
  <?php foreach ($tenants as $t): ?>
    <tr>
      <td><a href="<?= site_url('tenants/' . $t['id']) ?>"><?= esc($t['company_name']) ?></a><div class="muted small"><?= esc($t['contact_name']) ?> · <?= esc($t['email']) ?></div></td>
      <td class="small"><a href="<?= esc(pf_workspace_url($t['slug']), 'attr') ?>" target="_blank" rel="noopener" style="font-weight:500"><?= esc($t['slug']) ?>.<?= esc(pf_root()) ?></a></td>
      <td><?= esc($t['package_name'] ?? '—') ?><div class="muted small"><?= $t['billing_cycle'] === 'annual' ? 'Annual' : 'Monthly' ?></div></td>
      <td><?= pf_status($t['status']) ?><?php if ($t['provision_error']): ?> <span class="badge s-suspended">Setup failed</span><?php endif; ?></td>
      <td class="nowrap"><?= $t['status'] === 'trial' ? pf_date($t['trial_ends_at']) : pf_date($t['current_period_end']) ?></td>
      <td class="nowrap muted"><?= pf_date($t['created_at']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (! $tenants): ?><tr><td colspan="6" class="empty">No workspaces found.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?= $this->endSection() ?>
