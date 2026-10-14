<?php
/** One invoice page. @var array $b BillingService::invoiceBundle() */
use App\Models\InvoiceModel;
use App\Models\VehicleModel;

$inv     = $b['invoice'];
$client  = $b['client'] ?? [];
$overdue = InvoiceModel::isOverdue($inv);
[$stamp, $stampColour] = match (true) {
    $inv['status'] === 'paid'    => ['PAID', '#15803d'],
    $inv['status'] === 'void'    => ['VOID', '#6b7682'],
    $inv['status'] === 'draft'   => ['DRAFT', '#6b7682'],
    $overdue                     => ['OVERDUE', '#b42318'],
    $inv['status'] === 'partial' => ['PART PAID', '#b45309'],
    default                      => ['DUE ' . date('j M Y', strtotime($inv['due_date'])), '#1f4e79'],
};
$vatRegistered = (float) $inv['vat_rate'] > 0;
?>
<?= view('billing/_company_head', [
    'docTitle'    => $vatRegistered ? 'TAX INVOICE' : 'INVOICE',
    'meta'        => [
        'Invoice no' => $inv['invoice_no'],
        'Issue date' => date('j M Y', strtotime($inv['issue_date'])),
        'Due date'   => date('j M Y', strtotime($inv['due_date'])),
        'Amount due' => money($b['balance']),
    ],
    'stamp'       => $stamp,
    'stampColour' => $stampColour,
], ['saveData' => false]) ?>

<table width="100%"><tr>
  <td width="52%" style="vertical-align:top;padding-right:12px">
    <div class="label">Bill to</div>
    <div class="party">
      <b><?= esc($client['name'] ?? '—') ?></b><br>
      <?php if (! empty($client['contact_person'])): ?>Attn: <?= esc($client['contact_person']) ?><br><?php endif; ?>
      <?php if (! empty($client['address'])): ?><?= nl2br(esc($client['address'])) ?><br><?php endif; ?>
      <?php if (! empty($client['vat_number'])): ?>VAT no: <?= esc($client['vat_number']) ?><br><?php endif; ?>
      <?php if (! empty($client['registration_number'])): ?>Reg no: <?= esc($client['registration_number']) ?><br><?php endif; ?>
      <span class="muted"><?= esc(trim(implode(' · ', array_filter([$client['email'] ?? '', $client['phone'] ?? ''])))) ?></span>
    </div>
  </td>
  <td width="48%" style="vertical-align:top">
    <?php if (! empty($b['inspection'])): $ins = $b['inspection']; $v = $b['vehicle'] ?? null; ?>
      <div class="label">Inspection</div>
      <div class="party">
        <b><?= esc($v ? model(VehicleModel::class)->label($v) : 'Vehicle') ?></b><br>
        <?php if (! empty($v['plate'])): ?>Registration: <?= esc($v['plate']) ?><br><?php endif; ?>
        <?php if (! empty($v['vin'])): ?><span class="mono small">VIN <?= esc($v['vin']) ?></span><br><?php endif; ?>
        Report <?= esc(strtoupper(substr((string) $ins['report_uid'], 0, 8))) ?> · <?= esc($ins['inspection_date']) ?>
      </div>
    <?php endif; ?>
  </td>
</tr></table>

<table class="items">
  <thead><tr><th width="56%">Description</th><th class="r" width="10%">Qty</th><th class="r" width="16%">Unit price</th><th class="r" width="18%">Amount</th></tr></thead>
  <tbody>
    <?php foreach ($b['items'] as $n => $it): ?>
      <tr class="<?= $n % 2 ? 'alt' : '' ?>">
        <td><?= esc($it['description']) ?></td>
        <td class="r"><?= esc(rtrim(rtrim(number_format((float) $it['qty'], 2, '.', ''), '0'), '.')) ?></td>
        <td class="r"><?= esc(money($it['unit_price'])) ?></td>
        <td class="r"><?= esc(money($it['line_total'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<table width="100%" style="margin-top:10px"><tr>
  <td width="52%" style="vertical-align:top;padding-right:12px">
    <?php if (! empty($b['payments'])): ?>
      <div class="label" style="margin-top:4px">Payments received</div>
      <table class="bank" width="100%">
        <?php foreach ($b['payments'] as $p): ?>
          <tr><td class="k"><?= esc(date('j M Y', strtotime($p['paid_at']))) ?> · <?= esc(\App\Models\PaymentModel::METHODS[$p['method']] ?? $p['method']) ?></td><td class="v" style="text-align:right"><?= esc(money($p['amount'])) ?></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </td>
  <td width="48%" style="vertical-align:top">
    <table class="totals" width="100%">
      <tr><td class="k">Subtotal<?= $vatRegistered ? ' (excl. VAT)' : '' ?></td><td class="v"><?= esc(money($inv['subtotal'])) ?></td></tr>
      <?php if ($vatRegistered): ?>
        <tr><td class="k">VAT @ <?= esc(rtrim(rtrim(number_format((float) $inv['vat_rate'], 2), '0'), '.')) ?>%</td><td class="v"><?= esc(money($inv['vat_amount'])) ?></td></tr>
      <?php endif; ?>
      <tr><td class="k" style="border-top:1px solid #dfe3e7">Total</td><td class="v" style="border-top:1px solid #dfe3e7"><?= esc(money($inv['total'])) ?></td></tr>
      <?php if ((float) $inv['amount_paid'] > 0): ?>
        <tr><td class="k">Paid</td><td class="v">− <?= esc(money($inv['amount_paid'])) ?></td></tr>
      <?php endif; ?>
    </table>
    <table width="100%" style="margin-top:6px"><tr><td class="due-box">
      <table width="100%"><tr>
        <td class="k">Amount due</td>
        <td class="v" style="text-align:right"><?= esc(money($b['balance'])) ?></td>
      </tr></table>
    </td></tr></table>
  </td>
</tr></table>

<?php if (! in_array($inv['status'], ['void', 'draft'], true)): ?>
  <?= view('billing/_payment_box', ['amountDue' => $b['balance'], 'payUrl' => $b['payUrl'], 'reference' => $inv['invoice_no']], ['saveData' => false]) ?>
<?php endif; ?>

<?php if (trim((string) $inv['notes']) !== '' || trim((string) setting('Billing.invoiceTerms')) !== ''): ?>
  <table width="100%" style="margin-top:14px"><tr>
    <?php if (trim((string) $inv['notes']) !== ''): ?>
      <td style="vertical-align:top;padding-right:12px"><div class="label">Notes</div><div class="notes"><?= nl2br(esc($inv['notes'])) ?></div></td>
    <?php endif; ?>
    <?php if (trim((string) setting('Billing.invoiceTerms')) !== ''): ?>
      <td style="vertical-align:top"><div class="label">Terms</div><div class="notes"><?= nl2br(esc((string) setting('Billing.invoiceTerms'))) ?></div></td>
    <?php endif; ?>
  </tr></table>
<?php endif; ?>

<div class="foot">Thank you for your business — <?= esc((string) billing('companyName')) ?>. View and pay online: <a href="<?= esc($b['payUrl']) ?>"><?= esc($b['payUrl']) ?></a></div>
