<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Settings<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Platform\Views\settings\_tabs', ['tabs' => $tabs, 'tab' => $tab]) ?>
<?php
$php   = '/opt/cpanel/ea-php84/root/usr/bin/php';
$root  = rtrim(str_replace('\\', '/', ROOTPATH), '/');
$bill  = 'cd ' . $root . ' && ' . $php . ' spark platform:billing --no-header >> ' . dirname($root) . '/billing.log 2>&1';
$cron  = 'cd ' . $root . ' && for s in $(' . $php . ' spark platform:pending --no-header); do CHECKA_TENANT=$s ' . $php . ' spark tenant:provision --no-header; done >> ' . dirname($root) . '/provision.log 2>&1';
?>
<form method="post" action="<?= site_url('settings/general') ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
    <div class="card">
      <h2>Platform</h2>
      <div class="f"><label>Platform name</label><input type="text" name="s[platformName]" value="<?= esc($s['platformName']) ?>"><small>Shown in this panel and in emails to your customers.</small></div>
      <div class="f"><label>Root domain</label><input type="text" name="s[rootDomain]" value="<?= esc($s['rootDomain']) ?>"><small>Workspaces live at <i>company</i>.<?= esc($s['rootDomain']) ?>. Needs a wildcard DNS record and SSL.</small></div>
      <div class="f"><label>Support email</label><input type="email" name="s[supportEmail]" value="<?= esc($s['supportEmail']) ?>"></div>
      <div class="grid g2">
        <div class="f"><label>Currency</label><select name="s[currency]"><?php foreach (['ZAR', 'USD', 'EUR', 'GBP'] as $c): ?><option <?= $s['currency'] === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
        <div class="f"><label>VAT rate %</label><input type="number" min="0" name="s[vatRate]" value="<?= esc((string) $s['vatRate']) ?>"></div>
      </div>
    </div>
    <div class="card">
      <h2>Your company <small>on invoices to customers</small></h2>
      <div class="f"><label>Company name</label><input type="text" name="s[companyName]" value="<?= esc($s['companyName']) ?>"></div>
      <div class="grid g2">
        <div class="f"><label>VAT number</label><input type="text" name="s[vatNumber]" value="<?= esc($s['vatNumber']) ?>"></div>
        <div class="f"><label>Invoice number prefix</label><input type="text" name="s[invoicePrefix]" value="<?= esc($s['invoicePrefix']) ?>"></div>
      </div>
      <div class="f"><label>Address</label><textarea name="s[companyAddress]" rows="4"><?= esc($s['companyAddress']) ?></textarea></div>
    </div>
  </div>
  <button class="btn">Save</button>
</form>

<div class="card" style="margin-top:22px">
  <h2>Automatic workspace setup (cron job)</h2>
  <p class="small">New workspaces are built by a background job. In cPanel → <b>Cron Jobs</b>, add this to run <b>every minute</b>:</p>
  <p><code><?= esc($cron) ?></code></p>
  <p class="small" style="margin-top:16px">And the <b>subscription robot</b> (invoices, reminders, card renewals, auto-pause, retention). Run it <b>every hour</b>:</p>
  <p><code><?= esc($bill) ?></code></p>
  <p class="muted small">Adjust the PHP path if your server uses a different PHP version.</p>
</div>
<?= $this->endSection() ?>
