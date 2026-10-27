<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $v = $vendor; ?>

<div class="page-head">
  <div><h1><?= esc($v['name']) ?></h1><div class="sub">Vendor<?= $v['account_number'] ? ' · our account ' . esc($v['account_number']) : '' ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('vendors/'.$v['uid'].'/edit') ?>">Edit</a>
  <a class="btn ghost" href="<?= site_url('purchase-orders/summary?vendor='.$v['id']) ?>">Summary</a>
  <a class="btn" href="<?= site_url('purchase-orders/new?vendor='.$v['uid']) ?>">New Purchase Order</a>
</div>

<div class="grid cols-2">
  <div class="card">
    <div class="card-h"><h3>Details</h3></div>
    <div class="card-b" style="display:grid;grid-template-columns:150px 1fr;gap:6px 12px;font-size:13.5px">
      <?php foreach (['contact_person' => 'Contact', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address', 'account_number' => 'Account no', 'vat_number' => 'VAT no', 'registration_number' => 'Reg no', 'notes' => 'Notes'] as $k => $l): if (! $v[$k]) continue; ?>
        <div class="mono" style="font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);padding-top:2px"><?= $l ?></div>
        <div><?= nl2br(esc($v[$k])) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-h"><h3>Orders</h3></div>
    <div class="card-b">
      <?php $open = array_filter($orders, static fn ($o) => $o['status'] !== 'cancelled'); ?>
      <div class="kpi" style="border:none;padding:0">
        <div class="k">Total ordered (priced, excl. cancelled)</div>
        <div class="v"><?= esc(money(array_sum(array_column($open, 'total')))) ?></div>
      </div>
      <div class="sub" style="font-size:12.5px;color:var(--ink-3);margin-top:6px"><?= count($orders) ?> purchase order<?= count($orders) === 1 ? '' : 's' ?></div>
    </div>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <div class="card-h"><h3>Purchase orders</h3></div>
  <?= view('purchase_orders/_table', ['orders' => $orders, 'hideVendor' => true], ['saveData' => false]) ?>
</div>

<?php if (auth()->user()->inGroup('admin')): ?>
<form method="post" action="<?= site_url('vendors/'.$v['uid'].'/delete') ?>" style="margin-top:22px" onsubmit="return confirm('Delete this vendor? Only possible if they have no purchase orders.')">
  <?= csrf_field() ?><button class="btn danger sm">Delete vendor</button>
</form>
<?php endif; ?>

<?= $this->endSection() ?>
