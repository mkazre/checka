<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>All clients<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $qs = http_build_query(array_filter(['q' => $q, 'tenant' => $tid])); ?>
<div class="head">
  <div><h1>All clients</h1><p class="sub">The clients your customers have added in their workspaces, in one place.</p></div>
  <a class="btn sec" href="<?= site_url('clients/export') . ($qs ? '?' . $qs : '') ?>">Export CSV</a>
</div>
<form class="filters" method="get">
  <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Search name, email, phone…">
  <select name="tenant" onchange="this.form.submit()">
    <option value="">All workspaces</option>
    <?php foreach ($tenants as $t): ?><option value="<?= $t['id'] ?>" <?= (int) $tid === (int) $t['id'] ? 'selected' : '' ?>><?= esc($t['company_name']) ?></option><?php endforeach; ?>
  </select>
  <button class="btn sec">Search</button>
</form>
<p class="muted small"><?= number_format(count($rows)) ?> client(s)<?= count($rows) >= 500 ? ' — showing up to 500 per workspace; the CSV export includes everything' : '' ?>.</p>
<div class="tbl">
<table>
  <thead><tr><th>Client</th><th>Contact</th><th>Workspace</th><th>Added</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><b><?= esc($r['name']) ?></b><div class="muted small"><?= esc(ucfirst((string) $r['type'])) ?><?= $r['vat_number'] ? ' · VAT ' . esc($r['vat_number']) : '' ?></div></td>
      <td class="small"><?= esc($r['contact_person'] ?? '') ?><div><?= esc($r['email'] ?? '') ?></div><div class="muted"><?= esc($r['phone'] ?? '') ?></div></td>
      <td class="small"><a href="<?= site_url('tenants/' . $r['_tid']) ?>"><?= esc($r['tenant']) ?></a></td>
      <td class="small muted nowrap"><?= pf_date($r['created_at']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (! $rows): ?><tr><td colspan="4" class="empty">No clients found.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?= $this->endSection() ?>
