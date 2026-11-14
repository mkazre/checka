<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Payment gateways<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Platform\Views\settings\_tabs', ['tabs' => $tabs, 'tab' => $tab]) ?>
<?php
$on  = static fn (string $g, string $k, string $label) => '<label class="check"><input type="hidden" name="s[' . $g . '][' . $k . ']" value="0"><input type="checkbox" name="s[' . $g . '][' . $k . ']" value="1" ' . (! empty($s[$g][$k]) ? 'checked' : '') . '><span>' . $label . '</span></label>';
$txt = static fn (string $g, string $k, string $label, string $hint = '') => '<div class="f"><label>' . $label . '</label><input type="text" name="s[' . $g . '][' . $k . ']" value="' . esc($s[$g][$k], 'attr') . '" autocomplete="off">' . ($hint ? '<small>' . $hint . '</small>' : '') . '</div>';
$sec = static fn (string $g, string $k, string $label) => '<div class="f"><label>' . $label . '</label><input type="password" name="s[' . $g . '][' . $k . ']" value="" autocomplete="new-password" placeholder="' . (! empty($secretSet[$g . '.' . $k]) ? '•••••••• saved — leave empty to keep' : 'not set') . '"></div>';
?>
<p class="muted small">Customers choose from the gateways you switch on here when they pay. Secrets are never shown again after saving.</p>
<form method="post" action="<?= site_url('settings/gateways') ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
    <div class="card">
      <h2>Paystack <small>card, instant EFT, saved cards for renewals</small></h2>
      <?= $on('paystack', 'enabled', 'Enabled') ?>
      <?= $txt('paystack', 'publicKey', 'Public key', 'pk_live_… or pk_test_…') ?>
      <?= $sec('paystack', 'secretKey', 'Secret key') ?>
      <p class="muted small">Webhook URL for your Paystack dashboard: <code><?= esc(site_url('webhooks/paystack')) ?></code></p>
    </div>
    <div class="card">
      <h2>PayFast</h2>
      <?= $on('payfast', 'enabled', 'Enabled') ?>
      <?= $on('payfast', 'sandbox', 'Sandbox (test mode)') ?>
      <?= $txt('payfast', 'merchantId', 'Merchant ID') ?>
      <?= $sec('payfast', 'merchantKey', 'Merchant key') ?>
      <?= $sec('payfast', 'passphrase', 'Passphrase') ?>
      <p class="muted small">ITN (notify) URL: <code><?= esc(site_url('webhooks/payfast')) ?></code></p>
    </div>
    <div class="card">
      <h2>Ozow <small>instant EFT</small></h2>
      <?= $on('ozow', 'enabled', 'Enabled') ?>
      <?= $on('ozow', 'sandbox', 'Test mode') ?>
      <?= $txt('ozow', 'siteCode', 'Site code') ?>
      <?= $sec('ozow', 'privateKey', 'Private key') ?>
      <?= $sec('ozow', 'apiKey', 'API key') ?>
      <p class="muted small">Notify URL: <code><?= esc(site_url('webhooks/ozow')) ?></code></p>
    </div>
    <div class="card">
      <h2>Manual EFT <small>bank transfer, marked paid by you</small></h2>
      <?= $on('eft', 'enabled', 'Show bank details on invoices') ?>
      <div class="grid g2">
        <?= $txt('eft', 'bankName', 'Bank') ?>
        <?= $txt('eft', 'accountName', 'Account name') ?>
        <?= $txt('eft', 'accountNumber', 'Account number') ?>
        <?= $txt('eft', 'branchCode', 'Branch code') ?>
      </div>
      <?= $txt('eft', 'accountType', 'Account type') ?>
      <div class="f"><label>Payment instructions</label><textarea name="s[eft][instructions]" rows="2"><?= esc($s['eft']['instructions']) ?></textarea></div>
    </div>
  </div>
  <button class="btn">Save gateways</button>
</form>
<?= $this->endSection() ?>
