<?php
$paid       = $inv['status'] === 'paid';
$void       = $inv['status'] === 'void';
$processing = ! $paid && service('request')->getGet('processing');
$head       = ($processing || ($paid && $t['status'] === 'provisioning')) ? '<meta http-equiv="refresh" content="6">' : '';
$recCard    = $method !== 'instant_eft';
$money      = static fn ($v) => \Platform\Services\Billing::money($v);
?>
<?= view('Platform\Views\public\_head', ['title' => 'Invoice ' . $inv['number'], 'extraHead' => $head]) ?>
<?php if ($welcome && ! $paid): ?>
  <div class="ok">Your workspace address <b><?= esc($t['slug']) ?></b> is reserved. Complete payment and we'll set everything up immediately.</div>
<?php endif; ?>
<?php if ($error): ?><div class="err"><?= esc($error) ?></div><?php endif; ?>

<div class="card">
  <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div><div class="mono">Invoice <?= esc($inv['number']) ?></div><h1 style="margin-top:4px"><?= $money($inv['total']) ?></h1>
      <div class="sub" style="margin:0"><?= esc($t['company_name']) ?> · due <?= date('d M Y', strtotime($inv['due_date'])) ?></div></div>
    <div style="text-align:right">
      <?php if ($paid): ?><span class="mono" style="color:#1f6b2c">● Paid <?= date('d M Y', strtotime($inv['paid_at'])) ?></span>
      <?php elseif ($void): ?><span class="mono">Cancelled</span>
      <?php else: ?><span class="mono" style="color:#a0640c">● Unpaid</span><?php endif; ?>
      <div><a href="<?= site_url('pay/' . $inv['uid'] . '/invoice') ?>" target="_blank" style="font-size:13px">View / print invoice</a></div>
    </div>
  </div>
  <table style="margin-top:18px">
    <tr><td><?= esc($inv['description']) ?><?php if ($inv['period_start'] && $inv['period_end']): ?><div style="color:#6b7178;font-size:13px"><?= date('d M Y', strtotime($inv['period_start'])) ?> – <?= date('d M Y', strtotime($inv['period_end'])) ?></div><?php endif; ?></td><td class="r"><?= $money($inv['subtotal']) ?></td></tr>
    <tr><td>VAT (<?= rtrim(rtrim((string) $inv['vat_rate'], '0'), '.') ?>%)</td><td class="r"><?= $money($inv['vat']) ?></td></tr>
    <tr class="tot"><td>Total</td><td class="r"><?= $money($inv['total']) ?></td></tr>
  </table>
</div>

<?php if ($paid): ?>
  <div class="card" style="text-align:center">
    <?php if ($t['status'] === 'provisioning'): ?>
      <div class="spin"></div><h2>Payment received — setting up your workspace</h2><p class="sub">This takes about a minute. This page refreshes by itself.</p>
    <?php else: ?>
      <h2>Thank you — payment received</h2><p class="sub">A receipt has been emailed to <?= esc($t['email']) ?>.</p>
      <a class="btn" href="<?= esc($login, 'attr') ?>">Go to your workspace</a>
    <?php endif; ?>
  </div>
<?php elseif ($processing): ?>
  <div class="card" style="text-align:center"><div class="spin"></div><h2>Confirming your payment…</h2><p class="sub">This usually takes a few seconds. This page refreshes by itself.</p></div>
<?php elseif (! $void): ?>
  <div class="card">
    <h2>Choose how to pay</h2>
    <div class="methods">
      <?php if ($paystack): ?>
        <form method="post" action="<?= site_url('pay/' . $inv['uid'] . '/paystack') ?>" class="method <?= $recCard ? 'rec' : '' ?>"><?= csrf_field() ?><input type="hidden" name="channel" value="card">
          <span><b>Card</b><small>Visa, Mastercard · your card is saved securely for automatic renewals</small></span><button class="btn">Pay <?= $money($inv['total']) ?></button></form>
      <?php endif; ?>
      <?php if ($ozow): ?>
        <form method="post" action="<?= site_url('pay/' . $inv['uid'] . '/ozow') ?>" class="method <?= ! $recCard ? 'rec' : '' ?>"><?= csrf_field() ?>
          <span><b>Instant EFT</b><small>Pay straight from your bank account (Ozow)</small></span><button class="btn <?= $recCard ? 'sec' : '' ?>">Pay with Ozow</button></form>
      <?php elseif ($paystack): ?>
        <form method="post" action="<?= site_url('pay/' . $inv['uid'] . '/paystack') ?>" class="method <?= ! $recCard ? 'rec' : '' ?>"><?= csrf_field() ?><input type="hidden" name="channel" value="eft">
          <span><b>Instant EFT</b><small>Pay from your bank account</small></span><button class="btn <?= $recCard ? 'sec' : '' ?>">Pay by EFT</button></form>
      <?php endif; ?>
      <?php if ($payfast): ?>
        <form method="post" action="<?= site_url('pay/' . $inv['uid'] . '/payfast') ?>" class="method <?= ! $paystack && ! $ozow ? 'rec' : '' ?>"><?= csrf_field() ?>
          <span><b>PayFast</b><small>Card, Instant EFT, SnapScan, Zapper and more</small></span><button class="btn sec">Pay with PayFast</button></form>
      <?php endif; ?>
      <?php if (! empty($eft['enabled'])): ?>
        <div class="method" style="display:block">
          <b>Bank transfer (EFT)</b>
          <table style="margin-top:8px;font-size:14px">
            <?php foreach (['bankName' => 'Bank', 'accountName' => 'Account name', 'accountNumber' => 'Account number', 'branchCode' => 'Branch code', 'accountType' => 'Account type'] as $k => $l): if (($eft[$k] ?? '') === '') continue; ?>
              <tr><td style="color:#6b7178"><?= $l ?></td><td><?= esc($eft[$k]) ?></td></tr>
            <?php endforeach; ?>
            <tr><td style="color:#6b7178">Reference</td><td><b><?= esc($inv['number']) ?></b></td></tr>
          </table>
          <small><?= esc($eft['instructions']) ?> Bank transfers take 1–2 working days to reflect; we'll activate your account as soon as it does.</small>
        </div>
      <?php endif; ?>
      <?php if (! $paystack && ! $ozow && ! $payfast && empty($eft['enabled'])): ?>
        <div class="err">Online payment isn't available yet. Please contact <?= esc($general['supportEmail'] ?: 'us') ?> to pay this invoice.</div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<p class="mono" style="text-align:center">Secure payment · <?= esc($general['companyName'] ?: $general['platformName']) ?></p>
</div></body></html>
