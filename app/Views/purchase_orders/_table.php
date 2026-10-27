<?php
/** @var list<array> $orders @var bool $hideVendor */
$hideVendor ??= false;
$badge = static function (string $s): string {
    $cls = ['draft' => 's-draft', 'sent' => 's-sent', 'received' => 's-paid', 'cancelled' => 's-void'][$s] ?? 's-draft';

    return '<span class="badge-s ' . $cls . '">' . esc(\App\Models\PurchaseOrderModel::STATUSES[$s] ?? $s) . '</span>';
};
?>
<?php if (empty($orders)): ?>
  <div class="empty" style="padding:24px 18px">No purchase orders here yet.</div>
<?php else: ?>
<div class="tbl-scroll">
<table class="tbl">
  <thead><tr><th>PO</th><?php if (! $hideVendor): ?><th>Vendor</th><?php endif; ?><th>Date</th><th>Description / vehicle</th><th>Status</th><th style="text-align:right">Total</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr onclick="location.href='<?= site_url('purchase-orders/'.$o['uid']) ?>'" style="cursor:pointer">
      <td class="mono"><a href="<?= site_url('purchase-orders/'.$o['uid']) ?>"><b><?= esc($o['po_no']) ?></b></a></td>
      <?php if (! $hideVendor): ?><td><?= esc($o['vendor_name'] ?? '—') ?></td><?php endif; ?>
      <td><?= esc($o['order_date']) ?></td>
      <td style="font-size:13px"><?= esc($o['description'] ?: '—') ?>
        <?php $veh = trim(implode(' ', array_filter([$o['veh_make'], $o['veh_model'], $o['veh_plate'] ? '· '.$o['veh_plate'] : '']))); if ($veh !== ''): ?><div class="sub" style="color:var(--ink-3);font-size:12px"><?= esc($veh) ?></div><?php endif; ?>
      </td>
      <td><?= $badge($o['status']) ?></td>
      <td style="text-align:right;font-weight:600"><?= (float) $o['total'] > 0 ? esc(money($o['total'])) : '<span class="sub" style="color:var(--ink-3);font-weight:400">Unpriced</span>' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
