<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
  $t   = static fn ($k) => esc((string) ($s[$k] ?? ''));
  $chk = static fn ($k) => ! empty($s[$k]) ? 'checked' : '';
  $run = session()->getFlashdata('runDetail');
?>

<div class="page-head">
  <div><h1>Billing Settings</h1><div class="sub">What appears on your invoices and statements, and how clients can pay.</div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('invoices') ?>">Invoices</a>
</div>

<form method="post" action="<?= site_url('invoices/settings') ?>">
  <?= csrf_field() ?>
  <div class="grid cols-2">
    <div class="card">
      <div class="card-h"><h3>Your company</h3></div>
      <div class="card-b grid" style="gap:12px">
        <label class="fld"><span>Company / trading name</span><input type="text" name="companyName" value="<?= $t('companyName') ?>" placeholder="<?= esc(brand('name')) ?>"></label>
        <label class="fld"><span>Address</span><textarea name="companyAddress" rows="3"><?= $t('companyAddress') ?></textarea></label>
        <div class="grid cols-2" style="gap:12px">
          <label class="fld"><span>Phone</span><input type="text" name="companyPhone" value="<?= $t('companyPhone') ?>"></label>
          <label class="fld"><span>Accounts email (reply-to)</span><input type="email" name="companyEmail" value="<?= $t('companyEmail') ?>"></label>
          <label class="fld"><span>Company reg. no</span><input type="text" name="companyRegNo" value="<?= $t('companyRegNo') ?>" class="mono"></label>
          <label class="fld"><span>VAT no</span><input type="text" name="companyVatNo" value="<?= $t('companyVatNo') ?>" class="mono"></label>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><h3>Tax, numbering &amp; defaults</h3></div>
      <div class="card-b grid" style="gap:12px">
        <label style="display:flex;gap:8px;align-items:center;font-weight:600"><input type="checkbox" name="vatRegistered" value="1" <?= $chk('vatRegistered') ?>> VAT registered (issue tax invoices)</label>
        <div class="grid cols-2" style="gap:12px">
          <label class="fld"><span>VAT rate %</span><input type="number" name="vatRate" step="0.01" min="0" max="100" value="<?= $t('vatRate') ?>"></label>
          <label class="fld"><span>Payment terms (days)</span><input type="number" name="paymentTermsDays" min="0" max="365" value="<?= $t('paymentTermsDays') ?>"></label>
          <label class="fld"><span>Invoice prefix</span><input type="text" name="invoicePrefix" value="<?= $t('invoicePrefix') ?>" class="mono" maxlength="12"></label>
          <label class="fld"><span>Next invoice number</span><input type="number" name="nextInvoiceNumber" min="1" value="<?= $t('nextInvoiceNumber') ?>" class="mono"></label>
          <label class="fld"><span>Default inspection fee</span><input type="number" name="defaultInspectionFee" step="0.01" min="0" value="<?= $t('defaultInspectionFee') ?>"></label>
          <label class="fld"><span>Default charge description</span><input type="text" name="defaultChargeDescription" value="<?= $t('defaultChargeDescription') ?>"></label>
        </div>
        <div class="sub" style="font-size:11.5px;color:var(--ink-3)">Next invoice will be <b class="mono"><?= $t('invoicePrefix') ?><?= $t('nextInvoiceNumber') ?></b>. Numbers already used are skipped automatically.</div>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><h3>Bank details (EFT)</h3><label style="margin-left:auto;display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" name="payEftEnabled" value="1" <?= $chk('payEftEnabled') ?>> Show on invoices</label></div>
      <div class="card-b grid cols-2" style="gap:12px">
        <label class="fld"><span>Bank</span><input type="text" name="bankName" value="<?= $t('bankName') ?>" placeholder="FNB / Standard Bank / Capitec…"></label>
        <label class="fld"><span>Account holder</span><input type="text" name="bankAccountName" value="<?= $t('bankAccountName') ?>"></label>
        <label class="fld"><span>Account number</span><input type="text" name="bankAccountNumber" value="<?= $t('bankAccountNumber') ?>" class="mono"></label>
        <label class="fld"><span>Branch code</span><input type="text" name="bankBranchCode" value="<?= $t('bankBranchCode') ?>" class="mono"></label>
        <label class="fld"><span>Account type</span><input type="text" name="bankAccountType" value="<?= $t('bankAccountType') ?>" placeholder="Business cheque"></label>
        <label class="fld"><span>SWIFT / BIC (optional)</span><input type="text" name="bankSwift" value="<?= $t('bankSwift') ?>" class="mono"></label>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><h3>Online payment (Pay Now)</h3></div>
      <div class="card-b grid" style="gap:12px">
        <label style="display:flex;gap:8px;align-items:center;font-weight:600"><input type="checkbox" name="payOnlineEnabled" value="1" <?= $chk('payOnlineEnabled') ?>> Show a Pay Now button on invoices, statements &amp; emails</label>
        <?php if ($paystackReady): ?>
          <div class="flash ok" style="margin:0">Paystack is connected — clients can pay by card or instant EFT. Payments are recorded against the invoice automatically.</div>
        <?php else: ?>
          <div class="flash err" style="margin:0">Paystack keys aren't set, so Pay Now is hidden. Add them under <a href="<?= site_url('settings') ?>"><b>Settings → Payments (Paystack)</b></a>. Bank details still show.</div>
        <?php endif; ?>
        <label class="fld"><span>Payment instructions</span><input type="text" name="paymentInstructions" value="<?= $t('paymentInstructions') ?>"></label>
        <label class="fld"><span>Invoice terms &amp; conditions</span><textarea name="invoiceTerms" rows="3" placeholder="e.g. Interest of 2% per month on overdue accounts."><?= $t('invoiceTerms') ?></textarea></label>
      </div>
    </div>

    <div class="card" style="grid-column:1/-1">
      <div class="card-h"><h3>Monthly statements</h3></div>
      <div class="card-b grid" style="gap:12px">
        <label style="display:flex;gap:8px;align-items:center;font-weight:600"><input type="checkbox" name="monthlyStatementsEnabled" value="1" <?= $chk('monthlyStatementsEnabled') ?>> Automatically email clients their statement + open invoices each month</label>
        <div class="sub" style="font-size:12.5px;color:var(--ink-2);line-height:1.6">
          Each opted-in client gets last month's statement with every unpaid invoice attached, your bank details and a Pay Now link — once per month.
          Clients with no activity and nothing owing are skipped. <?= $emailReady ? '' : '<b style="color:var(--major)">Email sending isn\'t configured yet (Settings → Notifications).</b>' ?>
        </div>
        <div class="sub" style="font-size:12px;color:var(--ink-3)">
          Runs from a server cron job (set up during deployment):
          <code class="mono" style="display:block;background:#f4f5f6;border-radius:7px;padding:8px 10px;margin-top:6px;color:var(--ink);overflow-x:auto;white-space:nowrap">0 7 1 * * /opt/cpanel/ea-php84/root/usr/bin/php <?= esc(ROOTPATH) ?>spark checka:statements</code>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:18px"><button class="btn" type="submit">Save billing settings</button></div>
</form>

<div class="card" style="margin-top:22px">
  <div class="card-h"><h3>Run statements now</h3><span class="sub" style="margin-left:auto;font-size:12px;color:var(--ink-3)">For <?= esc($lastPeriod) ?></span></div>
  <div class="card-b">
    <form method="post" action="<?= site_url('invoices/statements/run') ?>" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap" onsubmit="return this.dry_run.checked || confirm('Email statements to all opted-in clients now?')">
      <?= csrf_field() ?>
      <label style="display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" name="dry_run" value="1" checked> Dry run (show who would get one, send nothing)</label>
      <label style="display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" name="force" value="1"> Resend to clients already sent this period</label>
      <button class="btn ghost">Run</button>
    </form>
    <?php if (is_array($run)): ?>
      <div style="margin-top:12px;font-size:13px;line-height:1.7">
        <?php foreach ($run['sent'] as $x): ?><div style="color:var(--ok)">✓ <?= esc($x) ?></div><?php endforeach; ?>
        <?php foreach ($run['failed'] as $x): ?><div style="color:var(--major)">✗ <?= esc($x) ?></div><?php endforeach; ?>
        <?php foreach ($run['skipped'] as $x): ?><div style="color:var(--ink-3)">– <?= esc($x) ?></div><?php endforeach; ?>
        <?php if (! $run['sent'] && ! $run['failed'] && ! $run['skipped']): ?><div class="sub">No clients are opted in to monthly statements.</div><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
