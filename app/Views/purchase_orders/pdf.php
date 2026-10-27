<?php
/**
 * Purchase order PDF — same letterhead and styles as invoices.
 * @var array $po @var array|null $vendor @var list<array> $items
 * @var bool $showPricing @var bool $showVehicle @var string $author @var int $unpriced
 */
use App\Models\PurchaseOrderModel;

$v       = $vendor ?? [];
$vat     = (float) $po['vat_rate'];
$fmtQty  = static fn ($q) => rtrim(rtrim(number_format((float) $q, 2, '.', ''), '0'), '.');
$stamp   = ['cancelled' => ['CANCELLED', '#b42318'], 'received' => ['RECEIVED', '#15803d'], 'draft' => ['DRAFT', '#6b7682']][$po['status']] ?? null;
?>
<?= view('billing/_pdf_styles', [], ['saveData' => false]) ?>

<?= view('billing/_company_head', [
    'docTitle'    => 'PURCHASE ORDER',
    'meta'        => array_filter([
        'PO no'        => $po['po_no'],
        'Order date'   => date('j M Y', strtotime($po['order_date'])),
        'Account no'   => (string) ($v['account_number'] ?? ''),
        'Reference'    => (string) ($po['reference'] ?? ''),
        'Created by'   => $author,
    ], static fn ($x) => $x !== ''),
    'stamp'       => $stamp[0] ?? null,
    'stampColour' => $stamp[1] ?? '#6b7682',
], ['saveData' => false]) ?>

<table width="100%"><tr>
  <td width="52%" style="vertical-align:top;padding-right:12px">
    <div class="label">Order to</div>
    <table width="100%" style="border-collapse:collapse"><tr><td class="party" style="border:1px solid #dfe3e7;padding:8px 10px">
      <b><?= esc($v['name'] ?? '—') ?></b><br>
      <?php if (! empty($v['contact_person'])): ?>Attn: <?= esc($v['contact_person']) ?><br><?php endif; ?>
      <?php if (! empty($v['address'])): ?><?= nl2br(esc($v['address'])) ?><br><?php endif; ?>
      <?php if (! empty($v['vat_number'])): ?>VAT no: <?= esc($v['vat_number']) ?><br><?php endif; ?>
      <?php if (! empty($v['registration_number'])): ?>Reg no: <?= esc($v['registration_number']) ?><br><?php endif; ?>
      <span class="muted"><?= esc(trim(implode(' · ', array_filter([$v['phone'] ?? '', $v['email'] ?? ''])))) ?></span>
    </td></tr></table>
  </td>
  <td width="48%" style="vertical-align:top">
    <?php if ($showVehicle): ?>
      <div class="label">Vehicle</div>
      <table class="bank" width="100%" style="border:1px solid #dfe3e7;padding:6px 10px">
        <?php foreach (PurchaseOrderModel::VEHICLE as $k => $label): if (trim((string) $po[$k]) === '') continue; ?>
          <tr><td class="k"><?= esc($label) ?></td><td class="v <?= $k === 'veh_vin' ? 'mono' : '' ?>"><?= esc($po[$k]) ?></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </td>
</tr></table>

<?php if (trim((string) $po['description']) !== ''): ?>
  <div class="label" style="margin-top:12px">Order description</div>
  <div style="font-size:9.5pt"><?= esc($po['description']) ?></div>
<?php endif; ?>

<table class="items">
  <thead><tr>
    <th width="5%">#</th>
    <th>Description</th>
    <th class="r" width="8%">Qty</th>
    <?php if ($showPricing): ?>
      <th class="r" width="14%">Unit price</th>
      <th class="r" width="8%">Tax rate</th>
      <th class="r" width="12%">Tax amount</th>
      <th class="r" width="15%">Amount incl. tax</th>
    <?php endif; ?>
  </tr></thead>
  <tbody>
    <?php foreach ($items as $n => $it): $has = $it['unit_price'] !== null; $ex = $has ? (float) $it['qty'] * (float) $it['unit_price'] : 0; $tax = round($ex * $vat / 100, 2); ?>
      <tr class="<?= $n % 2 ? 'alt' : '' ?>">
        <td><?= $n + 1 ?></td>
        <td><?= esc($it['description']) ?><?= $it['part_no'] ? '<br><span class="muted small mono">Part no ' . esc($it['part_no']) . '</span>' : '' ?></td>
        <td class="r"><?= esc($fmtQty($it['qty'])) ?></td>
        <?php if ($showPricing): ?>
          <td class="r"><?= $has ? esc(money($it['unit_price'], false)) : '<span class="muted">TBA</span>' ?></td>
          <td class="r"><?= $has ? esc($fmtQty($vat)) : '' ?></td>
          <td class="r"><?= $has ? esc(money($tax, false)) : '' ?></td>
          <td class="r"><b><?= $has ? esc(money($ex + $tax, false)) : '' ?></b></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<table width="100%" style="margin-top:14px"><tr>
  <td width="52%" style="vertical-align:bottom;padding-right:12px">
    <table width="100%"><tr>
      <td width="55%" style="border-bottom:1px solid #1f2933;height:34px"></td><td width="5%"></td>
      <td style="border-bottom:1px solid #1f2933"></td>
    </tr><tr>
      <td class="small" style="padding-top:3px"><b>Authorised by</b></td><td></td><td class="small" style="padding-top:3px"><b>Date</b></td>
    </tr></table>
  </td>
  <td width="48%" style="vertical-align:top">
    <?php if ($showPricing): ?>
      <table class="totals" width="100%">
        <tr><td class="k">Nett total</td><td class="v"><?= esc(money($po['subtotal'])) ?></td></tr>
        <tr><td class="k">VAT @ <?= esc($fmtQty($vat)) ?>%</td><td class="v"><?= esc(money($po['vat_amount'])) ?></td></tr>
      </table>
      <table width="100%" style="margin-top:6px"><tr><td class="due-box">
        <table width="100%"><tr><td class="k">Order total</td><td class="v" style="text-align:right"><?= esc(money($po['total'])) ?></td></tr></table>
      </td></tr></table>
      <?php if ($unpriced > 0): ?><div class="small muted" style="margin-top:4px">Excludes <?= $unpriced ?> line<?= $unpriced === 1 ? '' : 's' ?> still to be priced (TBA).</div><?php endif; ?>
    <?php endif; ?>
  </td>
</tr></table>

<?php if (trim((string) $po['notes']) !== ''): ?>
  <div class="label" style="margin-top:14px">Notes</div>
  <div class="notes"><?= nl2br(esc($po['notes'])) ?></div>
<?php endif; ?>

<div class="foot">Please quote <b><?= esc($po['po_no']) ?></b> on your invoice and delivery note. Queries: <?= esc((string) billing('companyName')) ?><?= setting('Billing.companyEmail') ? ' · ' . esc((string) setting('Billing.companyEmail')) : '' ?><?= setting('Billing.companyPhone') ? ' · ' . esc((string) setting('Billing.companyPhone')) : '' ?></div>
