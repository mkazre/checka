<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<style>
  .po-wrap{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:16px;align-items:start}
  @media(max-width:980px){.po-wrap{grid-template-columns:1fr}}
  .paper{background:#fff;border:1px solid var(--line);border-radius:12px;padding:26px 28px;box-shadow:0 12px 40px rgba(16,23,29,.06)}
  .paper .top{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;border-bottom:3px solid var(--brand);padding-bottom:14px;margin-bottom:16px}
  .paper .title{font-family:var(--display);font-size:24px;font-weight:800;letter-spacing:.04em;color:var(--brand);text-align:right}
  .lbl{font-family:var(--mono);font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3);font-weight:700;margin-bottom:4px}
  .items{width:100%;border-collapse:collapse;margin-top:16px}
  .items th{background:var(--ink);color:#fff;font-family:var(--mono);font-size:10px;letter-spacing:.1em;text-transform:uppercase;padding:9px 10px;text-align:left}
  .items td{padding:9px 10px;border-bottom:1px solid var(--line);vertical-align:top}
  .items .r{text-align:right}
  .side .card{margin-bottom:14px}
  .side form{display:grid;gap:10px}
  .sw{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:13.5px;padding:6px 0}
  .tba{color:var(--lesser);font-weight:600;font-size:12px}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
  use App\Models\PurchaseOrderModel;

  $v       = $vendor ?? [];
  $vat     = (float) $po['vat_rate'];
  $hasVeh  = PurchaseOrderModel::hasVehicle($po);
  $fmtQty  = static fn ($q) => rtrim(rtrim(number_format((float) $q, 2, '.', ''), '0'), '.');
  $isAdmin = auth()->user()->inGroup('admin');
  $cls     = ['draft' => 's-draft', 'sent' => 's-sent', 'received' => 's-paid', 'cancelled' => 's-void'][$po['status']] ?? 's-draft';
?>

<div class="page-head">
  <div><h1><?= esc($po['po_no']) ?> <span class="badge-s <?= $cls ?>"><?= esc(PurchaseOrderModel::STATUSES[$po['status']] ?? $po['status']) ?></span></h1>
    <div class="sub"><?= esc($v['name'] ?? '') ?> · <?= esc($po['order_date']) ?><?= $po['sent_at'] ? ' · emailed ' . esc(substr($po['sent_at'], 0, 16)) : '' ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('purchase-orders') ?>">All POs</a>
  <a class="btn ghost" href="<?= site_url('purchase-orders/'.$po['uid'].'/edit') ?>">Edit</a>
  <a class="btn dark" target="_blank" href="<?= site_url('purchase-orders/'.$po['uid'].'/pdf') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg> PDF
  </a>
</div>

<div class="po-wrap">
  <div class="paper">
    <div class="top">
      <div>
        <img src="<?= esc(po_logo()) ?>" alt="" style="height:60px"><br>
        <b style="font-size:15px"><?= esc((string) billing('companyName')) ?></b>
        <div class="sub" style="font-size:12px;color:var(--ink-3);line-height:1.5"><?= nl2br(esc((string) setting('Billing.companyAddress'))) ?></div>
      </div>
      <div>
        <div class="title">PURCHASE ORDER</div>
        <div style="display:grid;grid-template-columns:auto auto;gap:2px 14px;font-size:13px;justify-content:end;margin-top:6px">
          <span style="color:var(--ink-3)">PO no</span><b class="mono"><?= esc($po['po_no']) ?></b>
          <span style="color:var(--ink-3)">Order date</span><b><?= esc(date('j M Y', strtotime($po['order_date']))) ?></b>
          <?php if (! empty($v['account_number'])): ?><span style="color:var(--ink-3)">Account no</span><b class="mono"><?= esc($v['account_number']) ?></b><?php endif; ?>
          <?php if ($po['reference']): ?><span style="color:var(--ink-3)">Reference</span><b><?= esc($po['reference']) ?></b><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="grid cols-2">
      <div>
        <div class="lbl">Order to</div>
        <b><a href="<?= site_url('vendors/'.($v['uid'] ?? '')) ?>"><?= esc($v['name'] ?? '—') ?></a></b>
        <div style="font-size:13px;color:var(--ink-2);line-height:1.5">
          <?= ! empty($v['contact_person']) ? 'Attn: ' . esc($v['contact_person']) . '<br>' : '' ?>
          <?= ! empty($v['address']) ? nl2br(esc($v['address'])) . '<br>' : '' ?>
          <span style="color:var(--ink-3)"><?= esc(trim(implode(' · ', array_filter([$v['phone'] ?? '', $v['email'] ?? ''])))) ?></span>
        </div>
      </div>
      <div style="<?= $po['show_vehicle'] ? '' : 'opacity:.45' ?>">
        <div class="lbl">Vehicle <?= $hasVeh && ! $po['show_vehicle'] ? '(hidden on PDF)' : '' ?></div>
        <?php if ($hasVeh): ?>
          <div style="display:grid;grid-template-columns:auto 1fr;gap:2px 12px;font-size:13px">
            <?php foreach (PurchaseOrderModel::VEHICLE as $k => $l): if (trim((string) $po[$k]) === '') continue; ?>
              <span style="color:var(--ink-3)"><?= $l ?></span><span class="<?= $k === 'veh_vin' ? 'mono' : '' ?>"><?= esc($po[$k]) ?></span>
            <?php endforeach; ?>
          </div>
        <?php else: ?><div class="sub" style="font-size:13px;color:var(--ink-3)">No vehicle details — add them via Edit.</div><?php endif; ?>
      </div>
    </div>

    <?php if ($po['description']): ?><div style="margin-top:14px"><div class="lbl">Order description</div><?= esc($po['description']) ?></div><?php endif; ?>

    <table class="items">
      <thead><tr><th>#</th><th>Description</th><th class="r">Qty</th><th class="r">Unit price</th><th class="r">Tax</th><th class="r">Amount incl.</th></tr></thead>
      <tbody>
      <?php foreach ($items as $n => $it): $has = $it['unit_price'] !== null; $ex = $has ? (float) $it['qty'] * (float) $it['unit_price'] : 0; $tax = round($ex * $vat / 100, 2); ?>
        <tr>
          <td><?= $n + 1 ?></td>
          <td><?= esc($it['description']) ?><?= $it['part_no'] ? '<div class="mono sub" style="font-size:11.5px;color:var(--ink-3)">Part no '.esc($it['part_no']).'</div>' : '' ?></td>
          <td class="r"><?= esc($fmtQty($it['qty'])) ?></td>
          <td class="r"><?= $has ? esc(money($it['unit_price'])) : '<span class="tba">TBA</span>' ?></td>
          <td class="r"><?= $has ? esc(money($tax)) : '' ?></td>
          <td class="r"><b><?= $has ? esc(money($ex + $tax)) : '' ?></b></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-left:auto;max-width:300px;margin-top:12px;font-size:14px;<?= $po['show_pricing'] ? '' : 'opacity:.45' ?>">
      <div style="display:flex;justify-content:space-between;padding:3px 0"><span style="color:var(--ink-3)">Nett total</span><span><?= esc(money($po['subtotal'])) ?></span></div>
      <div style="display:flex;justify-content:space-between;padding:3px 0"><span style="color:var(--ink-3)">VAT @ <?= esc($fmtQty($vat)) ?>%</span><span><?= esc(money($po['vat_amount'])) ?></span></div>
      <div style="display:flex;justify-content:space-between;align-items:center;background:var(--brand);color:#fff;border-radius:9px;padding:10px 12px;margin-top:6px"><span>Order total</span><b style="font-size:19px"><?= esc(money($po['total'])) ?></b></div>
      <?php if (! $po['show_pricing']): ?><div style="font-size:12px;color:var(--ink-3);margin-top:4px">Pricing is hidden on the PDF.</div><?php endif; ?>
    </div>
    <?php if ($po['notes']): ?><div style="margin-top:16px"><div class="lbl">Notes</div><div style="font-size:13px;color:var(--ink-2)"><?= nl2br(esc($po['notes'])) ?></div></div><?php endif; ?>
  </div>

  <div class="side">
    <div class="card">
      <div class="card-h"><h3>PDF</h3></div>
      <div class="card-b">
        <form method="get" action="<?= site_url('purchase-orders/'.$po['uid'].'/pdf') ?>" target="_blank">
          <label style="display:flex;gap:8px;align-items:center;font-size:13.5px"><input type="hidden" name="pricing" value="0"><input type="checkbox" name="pricing" value="1" <?= $po['show_pricing'] ? 'checked' : '' ?>> Include pricing</label>
          <label style="display:flex;gap:8px;align-items:center;font-size:13.5px;<?= $hasVeh ? '' : 'opacity:.5' ?>"><input type="hidden" name="vehicle" value="0"><input type="checkbox" name="vehicle" value="1" <?= $po['show_vehicle'] && $hasVeh ? 'checked' : '' ?> <?= $hasVeh ? '' : 'disabled' ?>> Include vehicle details</label>
          <div style="display:flex;gap:6px">
            <button class="btn sm" type="submit">Open PDF</button>
            <button class="btn ghost sm" type="submit" name="download" value="1">Download</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><h3>Defaults for this PO</h3></div>
      <div class="card-b">
        <form method="post" action="<?= site_url('purchase-orders/'.$po['uid'].'/toggle/pricing') ?>" class="sw"><?= csrf_field() ?>
          <span>Pricing on PDF &amp; emails</span><button class="btn sm <?= $po['show_pricing'] ? '' : 'ghost' ?>"><?= $po['show_pricing'] ? 'Included' : 'Excluded' ?></button>
        </form>
        <form method="post" action="<?= site_url('purchase-orders/'.$po['uid'].'/toggle/vehicle') ?>" class="sw"><?= csrf_field() ?>
          <span>Vehicle details</span><button class="btn sm <?= $po['show_vehicle'] ? '' : 'ghost' ?>"><?= $po['show_vehicle'] ? 'Included' : 'Excluded' ?></button>
        </form>
        <?php if ($priced > 0): ?>
        <form method="post" action="<?= site_url('purchase-orders/'.$po['uid'].'/clear-prices') ?>" onsubmit="return confirm('Remove all <?= $priced ?> price(s) from this PO? The items stay; you can add prices again later.')" style="margin-top:8px"><?= csrf_field() ?>
          <button class="btn ghost sm">Remove all prices</button>
        </form>
        <?php endif; ?>
        <div class="sub" style="font-size:11.5px;color:var(--ink-3);margin-top:8px"><?= $priced ?> of <?= count($items) ?> item(s) priced. Add or change prices under Edit.</div>
      </div>
    </div>

    <?php if ($po['status'] !== 'cancelled'): ?>
    <div class="card">
      <div class="card-h"><h3>Email to vendor</h3></div>
      <div class="card-b">
        <form method="post" action="<?= site_url('purchase-orders/'.$po['uid'].'/send') ?>">
          <?= csrf_field() ?>
          <label class="fld"><span>To</span><input type="email" name="email" value="<?= esc($v['email'] ?? '') ?>" required></label>
          <label style="display:flex;gap:8px;align-items:center;font-size:13px"><input type="checkbox" name="pricing" value="1" <?= $po['show_pricing'] ? 'checked' : '' ?>> Include pricing</label>
          <label style="display:flex;gap:8px;align-items:center;font-size:13px"><input type="checkbox" name="vehicle" value="1" <?= $po['show_vehicle'] && $hasVeh ? 'checked' : '' ?> <?= $hasVeh ? '' : 'disabled' ?>> Include vehicle details</label>
          <button class="btn">Send purchase order</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-h"><h3>Status</h3></div>
      <div class="card-b">
        <form method="post" action="<?= site_url('purchase-orders/'.$po['uid'].'/status') ?>" style="display:flex;gap:6px;flex-wrap:wrap"><?= csrf_field() ?>
          <?php foreach (PurchaseOrderModel::STATUSES as $k => $l): ?>
            <button class="btn sm <?= $po['status'] === $k ? 'dark' : 'ghost' ?>" name="status" value="<?= $k ?>" <?= $po['status'] === $k ? 'disabled' : '' ?>><?= $l ?></button>
          <?php endforeach; ?>
        </form>
      </div>
    </div>

    <?php if ($isAdmin): ?>
    <form method="post" action="<?= site_url('purchase-orders/'.$po['uid'].'/delete') ?>" onsubmit="return confirm('Delete <?= esc($po['po_no'], 'js') ?>? Prefer Cancelled if it was already sent.')"><?= csrf_field() ?>
      <button class="btn danger sm">Delete purchase order</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
