<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $qs = http_build_query(array_filter(['from' => $f['from'], 'to' => $f['to'], 'vendor' => $f['vendor_id'], 'status' => $f['status']])); ?>

<div class="page-head">
  <div><h1>Purchase Order Summary</h1><div class="sub"><?= esc(date('j M Y', strtotime($f['from']))) ?> – <?= esc(date('j M Y', strtotime($f['to']))) ?><?= $vendor ? ' · ' . esc($vendor['name']) : '' ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('purchase-orders') ?>">All POs</a>
  <a class="btn dark" target="_blank" href="<?= site_url('purchase-orders/summary?pdf=1&' . $qs) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg> PDF
  </a>
</div>

<form method="get" action="<?= site_url('purchase-orders/summary') ?>" class="card" style="margin-bottom:16px">
  <div class="card-b" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <label class="fld"><span>From</span><input type="date" name="from" value="<?= esc($f['from']) ?>"></label>
    <label class="fld"><span>To</span><input type="date" name="to" value="<?= esc($f['to']) ?>"></label>
    <label class="fld"><span>Vendor</span>
      <select name="vendor"><option value="">All vendors</option>
        <?php foreach ($vendors as $v): ?><option value="<?= $v['id'] ?>" <?= (int) $f['vendor_id'] === (int) $v['id'] ? 'selected' : '' ?>><?= esc($v['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="fld"><span>Status</span>
      <select name="status"><option value="">All statuses</option>
        <?php foreach (\App\Models\PurchaseOrderModel::STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select>
    </label>
    <button class="btn">Show</button>
  </div>
</form>

<div class="grid cols-4" style="margin-bottom:16px">
  <div class="kpi"><div class="k">Purchase orders</div><div class="v"><?= (int) $tot['count'] ?></div></div>
  <div class="kpi"><div class="k">Nett (excl. VAT)</div><div class="v"><?= esc(money($tot['subtotal'])) ?></div></div>
  <div class="kpi"><div class="k">VAT</div><div class="v"><?= esc(money($tot['vat'])) ?></div></div>
  <div class="kpi"><div class="k">Total incl. VAT</div><div class="v" style="color:var(--brand)"><?= esc(money($tot['total'])) ?></div></div>
</div>

<?php if (count($byVendor) > 1): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-h"><h3>By vendor</h3></div>
  <table class="tbl">
    <thead><tr><th>Vendor</th><th style="text-align:right">POs</th><th style="text-align:right">Total incl. VAT</th></tr></thead>
    <tbody><?php foreach ($byVendor as $name => $r): ?><tr><td><?= esc($name) ?></td><td style="text-align:right"><?= $r['count'] ?></td><td style="text-align:right;font-weight:600"><?= esc(money($r['total'])) ?></td></tr><?php endforeach; ?></tbody>
  </table>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-h"><h3>Orders</h3><?php if (! empty($byStatus['cancelled'])): ?><span class="sub" style="margin-left:auto;font-size:12px;color:var(--ink-3)">Cancelled orders are listed but not totalled.</span><?php endif; ?></div>
  <?= view('purchase_orders/_table', ['orders' => $orders], ['saveData' => false]) ?>
</div>

<?= $this->endSection() ?>
