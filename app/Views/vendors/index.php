<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Vendors</h1><div class="sub">Suppliers you raise purchase orders with — parts, panel shops, services.</div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('purchase-orders') ?>">Purchase Orders</a>
  <a class="btn" href="<?= site_url('vendors/new') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> New Vendor
  </a>
</div>

<div class="card">
  <div class="card-h">
    <h3>Register <span class="sub" style="font-weight:400;color:var(--ink-3)">(<?= count($vendors) ?>)</span></h3>
    <form method="get" action="<?= site_url('vendors') ?>" style="margin-left:auto;display:flex;gap:6px">
      <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Search name, contact, account no…" style="min-width:240px;padding:7px 10px;font-size:13px">
      <?php if ($q !== ''): ?><a class="btn ghost sm" href="<?= site_url('vendors') ?>">Clear</a><?php endif; ?>
    </form>
  </div>
  <?php if (empty($vendors)): ?>
    <div class="empty" style="padding:28px 18px"><?= $q !== '' ? 'No vendors match your search.' : 'No vendors yet — add your first supplier to start raising purchase orders.' ?></div>
  <?php else: ?>
  <div class="tbl-scroll">
  <table class="tbl">
    <thead><tr><th>Vendor</th><th>Contact</th><th>Phone / Email</th><th>Our account no</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($vendors as $v): ?>
      <tr>
        <td><a href="<?= site_url('vendors/'.$v['uid']) ?>"><b><?= esc($v['name']) ?></b></a><?= $v['vat_number'] ? '<div class="sub" style="font-size:11.5px;color:var(--ink-3)">VAT '.esc($v['vat_number']).'</div>' : '' ?></td>
        <td><?= esc($v['contact_person'] ?: '—') ?></td>
        <td style="font-size:13px"><?= esc($v['phone'] ?: '') ?><div class="sub" style="color:var(--ink-3)"><?= esc($v['email'] ?: '') ?></div></td>
        <td class="mono" style="font-size:12.5px"><?= esc($v['account_number'] ?: '—') ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="<?= site_url('purchase-orders/new?vendor='.$v['uid']) ?>">New PO</a>
          <a class="btn ghost sm" href="<?= site_url('vendors/'.$v['uid']) ?>">Open</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
