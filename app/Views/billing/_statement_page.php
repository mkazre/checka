<?php
/**
 * Client statement page.
 * @var array  $client
 * @var array  $st      BillingService::statement()
 * @var string $payUrl  pay-the-account page
 */
$fmtDate = static fn ($d) => date('j M Y', strtotime($d));
$closing = (float) $st['closing'];
?>
<?= view('billing/_company_head', [
    'docTitle'    => 'STATEMENT',
    'meta'        => [
        'Statement date' => $fmtDate($st['to']),
        'Period'         => $fmtDate($st['from']) . ' – ' . $fmtDate($st['to']),
        'Account'        => strtoupper(substr((string) $client['uid'], 0, 8)),
        $closing < 0 ? 'Credit balance' : 'Balance due' => money(abs($closing)),
    ],
    'stamp'       => $closing <= 0 ? 'ACCOUNT SETTLED' : null,
    'stampColour' => '#15803d',
], ['saveData' => false]) ?>

<table width="100%"><tr>
  <td width="60%" style="vertical-align:top">
    <div class="label">Statement for</div>
    <div class="party">
      <b><?= esc($client['name']) ?></b><br>
      <?php if (! empty($client['contact_person'])): ?>Attn: <?= esc($client['contact_person']) ?><br><?php endif; ?>
      <?php if (! empty($client['address'])): ?><?= nl2br(esc($client['address'])) ?><br><?php endif; ?>
      <?php if (! empty($client['vat_number'])): ?>VAT no: <?= esc($client['vat_number']) ?><br><?php endif; ?>
    </div>
  </td>
  <td width="40%" style="vertical-align:top">
    <table class="totals" width="100%">
      <tr><td class="k">Opening balance</td><td class="v"><?= esc(money($st['opening'])) ?></td></tr>
      <tr><td class="k">Invoiced this period</td><td class="v"><?= esc(money($st['debits'])) ?></td></tr>
      <tr><td class="k">Payments received</td><td class="v">− <?= esc(money($st['credits'])) ?></td></tr>
    </table>
    <table width="100%" style="margin-top:6px"><tr><td class="due-box">
      <table width="100%"><tr>
        <td class="k"><?= $closing < 0 ? 'Credit balance' : 'Balance due' ?></td>
        <td class="v" style="text-align:right"><?= esc(money(abs($closing))) ?></td>
      </tr></table>
    </td></tr></table>
  </td>
</tr></table>

<table class="ledger">
  <thead><tr><th width="14%">Date</th><th width="16%">Reference</th><th>Description</th><th class="r" width="13%">Debit</th><th class="r" width="13%">Credit</th><th class="r" width="14%">Balance</th></tr></thead>
  <tbody>
    <tr class="open"><td><?= esc($fmtDate($st['from'])) ?></td><td></td><td>Opening balance</td><td></td><td></td><td class="r"><?= esc(money($st['opening'])) ?></td></tr>
    <?php foreach ($st['lines'] as $l): ?>
      <tr>
        <td><?= esc($fmtDate($l['date'])) ?></td>
        <td class="mono small"><?= esc((string) $l['ref']) ?></td>
        <td><?= esc($l['description']) ?></td>
        <td class="r"><?= $l['debit'] > 0 ? esc(money($l['debit'])) : '' ?></td>
        <td class="r"><?= $l['credit'] > 0 ? esc(money($l['credit'])) : '' ?></td>
        <td class="r"><?= esc(money($l['balance'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($st['lines'] === []): ?>
      <tr><td colspan="6" class="muted" style="text-align:center;padding:12px">No activity in this period.</td></tr>
    <?php endif; ?>
    <tr class="open"><td><?= esc($fmtDate($st['to'])) ?></td><td></td><td>Closing balance</td><td></td><td></td><td class="r"><?= esc(money($closing)) ?></td></tr>
  </tbody>
</table>

<div class="label" style="margin-top:14px">Account ageing (outstanding by days overdue)</div>
<table class="ageing">
  <thead><tr><th>Current</th><th>1–30 days</th><th>31–60 days</th><th>61–90 days</th><th>90+ days</th></tr></thead>
  <tbody><tr>
    <td><?= esc(money($st['ageing']['current'])) ?></td>
    <td><?= esc(money($st['ageing']['d30'])) ?></td>
    <td><?= esc(money($st['ageing']['d60'])) ?></td>
    <td style="color:<?= $st['ageing']['d90'] > 0 ? '#b42318' : 'inherit' ?>"><?= esc(money($st['ageing']['d90'])) ?></td>
    <td style="color:<?= $st['ageing']['d90plus'] > 0 ? '#b42318' : 'inherit' ?>"><?= esc(money($st['ageing']['d90plus'])) ?></td>
  </tr></tbody>
</table>

<?php if ($st['open'] !== []): ?>
  <div class="label" style="margin-top:14px">Open invoices<?= count($st['open']) ? ' (attached on the following pages)' : '' ?></div>
  <table class="ledger" style="margin-top:4px">
    <thead><tr><th>Invoice</th><th>Issued</th><th>Due</th><th class="r">Total</th><th class="r">Paid</th><th class="r">Outstanding</th></tr></thead>
    <tbody>
      <?php foreach ($st['open'] as $o): ?>
        <tr>
          <td class="mono"><?= esc($o['invoice_no']) ?></td>
          <td><?= esc($fmtDate($o['issue_date'])) ?></td>
          <td style="color:<?= $o['due_date'] < date('Y-m-d') ? '#b42318' : 'inherit' ?>"><?= esc($fmtDate($o['due_date'])) ?></td>
          <td class="r"><?= esc(money($o['total'])) ?></td>
          <td class="r"><?= esc(money($o['amount_paid'])) ?></td>
          <td class="r"><b><?= esc(money(\App\Models\InvoiceModel::balance($o))) ?></b></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?= view('billing/_payment_box', ['amountDue' => $closing, 'payUrl' => $payUrl, 'reference' => strtoupper(substr((string) $client['uid'], 0, 8)), 'accountRef' => true], ['saveData' => false]) ?>

<div class="foot">Queries about this statement? Contact <?= esc((string) billing('companyName')) ?><?= setting('Billing.companyEmail') ? ' at ' . esc((string) setting('Billing.companyEmail')) : '' ?>. Pay online: <a href="<?= esc($payUrl) ?>"><?= esc($payUrl) ?></a></div>
