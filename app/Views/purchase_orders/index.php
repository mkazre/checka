<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Purchase Orders</h1><div class="sub">Orders you send to vendors for parts and services.</div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('vendors') ?>">Vendors</a>
  <a class="btn ghost" href="<?= site_url('purchase-orders/summary') ?>">Summary</a>
  <a class="btn" href="<?= site_url('purchase-orders/new') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> New Purchase Order
  </a>
</div>

<div class="card">
  <div class="card-h" style="flex-wrap:wrap;gap:8px">
    <div style="display:flex;gap:4px;flex-wrap:wrap">
      <?php foreach (['' => 'All'] + \App\Models\PurchaseOrderModel::STATUSES as $k => $l): ?>
        <a class="btn sm <?= $f['status'] === $k ? 'dark' : 'ghost' ?>" href="<?= site_url('purchase-orders'.($k !== '' ? '?status='.$k : '')) ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    <form method="get" action="<?= site_url('purchase-orders') ?>" style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap">
      <?php if ($f['status'] !== ''): ?><input type="hidden" name="status" value="<?= esc($f['status']) ?>"><?php endif; ?>
      <select name="vendor" style="width:auto;padding:7px 10px;font-size:13px" onchange="this.form.submit()">
        <option value="">All vendors</option>
        <?php foreach ($vendors as $v): ?><option value="<?= $v['id'] ?>" <?= (int) $f['vendor_id'] === (int) $v['id'] ? 'selected' : '' ?>><?= esc($v['name']) ?></option><?php endforeach; ?>
      </select>
      <input type="text" name="q" value="<?= esc($f['q']) ?>" placeholder="PO no, VIN, reg, description…" style="min-width:220px;padding:7px 10px;font-size:13px">
    </form>
  </div>
  <?= view('purchase_orders/_table', ['orders' => $orders], ['saveData' => false]) ?>
</div>

<?= $this->endSection() ?>
