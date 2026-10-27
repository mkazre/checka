<?php
/** PO summary statement PDF — same letterhead/styles as invoices. */
$fmt = static fn ($d) => date('j M Y', strtotime($d));
?>
<?= view('billing/_pdf_styles', [], ['saveData' => false]) ?>
<?= view('billing/_company_head', [
    'docTitle'    => 'PURCHASE SUMMARY',
    'meta'        => array_filter([
        'Period'  => $fmt($f['from']) . ' – ' . $fmt($f['to']),
        'Vendor'  => $vendor['name'] ?? 'All vendors',
        'Status'  => $f['status'] !== '' ? (\App\Models\PurchaseOrderModel::STATUSES[$f['status']] ?? $f['status']) : '',
        'Printed' => date('j M Y H:i'),
    ]),
    'stamp'       => null,
    'stampColour' => '',
], ['saveData' => false]) ?>

<table width="100%"><tr>
  <td width="58%"></td>
  <td width="42%">
    <table class="totals" width="100%">
      <tr><td class="k">Purchase orders</td><td class="v"><?= (int) $tot['count'] ?></td></tr>
      <tr><td class="k">Nett (excl. VAT)</td><td class="v"><?= esc(money($tot['subtotal'])) ?></td></tr>
      <tr><td class="k">VAT</td><td class="v"><?= esc(money($tot['vat'])) ?></td></tr>
    </table>
    <table width="100%" style="margin-top:6px"><tr><td class="due-box">
      <table width="100%"><tr><td class="k">Total incl. VAT</td><td class="v" style="text-align:right"><?= esc(money($tot['total'])) ?></td></tr></table>
    </td></tr></table>
  </td>
</tr></table>

<?php if (count($byVendor) > 1): ?>
<div class="label" style="margin-top:14px">By vendor</div>
<table class="ledger">
  <thead><tr><th>Vendor</th><th class="r" width="12%">POs</th><th class="r" width="22%">Total incl. VAT</th></tr></thead>
  <tbody><?php foreach ($byVendor as $name => $r): ?><tr><td><?= esc($name) ?></td><td class="r"><?= $r['count'] ?></td><td class="r"><b><?= esc(money($r['total'])) ?></b></td></tr><?php endforeach; ?></tbody>
</table>
<?php endif; ?>

<div class="label" style="margin-top:14px">Purchase orders</div>
<table class="ledger">
  <thead><tr><th width="12%">Date</th><th width="12%">PO no</th><?php if (! $vendor): ?><th width="22%">Vendor</th><?php endif; ?><th>Description / vehicle</th><th width="10%">Status</th><th class="r" width="14%">Nett</th><th class="r" width="15%">Total incl.</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): $cx = $o['status'] === 'cancelled'; ?>
    <tr style="<?= $cx ? 'color:#9aa3ad' : '' ?>">
      <td><?= esc($fmt($o['order_date'])) ?></td>
      <td class="mono"><?= esc($o['po_no']) ?></td>
      <?php if (! $vendor): ?><td><?= esc($o['vendor_name'] ?? '—') ?></td><?php endif; ?>
      <td><?= esc($o['description'] ?: '—') ?><?php $veh = trim(implode(' ', array_filter([$o['veh_make'], $o['veh_model'], $o['veh_plate']]))); if ($veh !== ''): ?><br><span class="muted small"><?= esc($veh) ?></span><?php endif; ?></td>
      <td><?= esc(\App\Models\PurchaseOrderModel::STATUSES[$o['status']] ?? $o['status']) ?></td>
      <td class="r"><?= (float) $o['subtotal'] > 0 ? esc(money($o['subtotal'], false)) : '<span class="muted">unpriced</span>' ?></td>
      <td class="r"><?= (float) $o['total'] > 0 ? '<b>' . esc(money($o['total'], false)) . '</b>' : '' ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($orders === []): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:12px">No purchase orders in this period.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php if (! empty($byStatus['cancelled'])): ?><div class="small muted" style="margin-top:6px">Cancelled orders (greyed) are listed but not included in the totals.</div><?php endif; ?>

<div class="foot"><?= esc((string) billing('companyName')) ?> · Purchase order summary <?= esc($fmt($f['from'])) ?> – <?= esc($fmt($f['to'])) ?></div>
