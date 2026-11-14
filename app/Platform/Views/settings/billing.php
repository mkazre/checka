<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Billing rules<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Platform\Views\settings\_tabs', ['tabs' => $tabs, 'tab' => $tab]) ?>
<?php $chk = static fn (string $k) => '<input type="hidden" name="s[' . $k . ']" value="0"><input type="checkbox" name="s[' . $k . ']" value="1" ' . (! empty($s[$k]) ? 'checked' : '') . '>'; ?>
<form method="post" action="<?= site_url('settings/billing') ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
    <div class="card">
      <h2>Invoices &amp; reminders</h2>
      <div class="f"><label>Issue the renewal invoice</label><div style="display:flex;gap:8px;align-items:center"><input type="number" min="0" name="s[invoiceLeadDays]" value="<?= (int) $s['invoiceLeadDays'] ?>" style="width:90px"> days before renewal</div></div>
      <div class="f"><label>Remind before the due date</label><input type="text" name="s[remindBefore]" value="<?= esc($s['remindBefore']) ?>" placeholder="7,3"><small>Days before, comma-separated. Empty = no reminders.</small></div>
      <label class="check"><?= $chk('remindOnDue') ?><span>Send a reminder on the due date</span></label>
      <div class="f"><label>Remind after the due date</label><input type="text" name="s[remindAfter]" value="<?= esc($s['remindAfter']) ?>" placeholder="3"><small>Days overdue, comma-separated.</small></div>
    </div>
    <div class="card">
      <h2>Non-payment</h2>
      <label class="check"><?= $chk('autoSuspend') ?><span>Pause workspaces automatically when unpaid</span></label>
      <div class="f"><label>Grace period</label><div style="display:flex;gap:8px;align-items:center"><input type="number" min="0" name="s[graceDays]" value="<?= (int) $s['graceDays'] ?>" style="width:90px"> days overdue before pausing</div></div>
      <label class="check"><?= $chk('autoUnsuspend') ?><span>Reactivate automatically as soon as payment arrives</span></label>
      <div class="f"><label>Keep data of paused / cancelled workspaces for</label><div style="display:flex;gap:8px;align-items:center"><input type="number" min="0" name="s[retentionDays]" value="<?= (int) $s['retentionDays'] ?>" style="width:90px"> days, then delete permanently</div><small>0 = never delete automatically.</small></div>
      <div class="f"><label>Final warning</label><div style="display:flex;gap:8px;align-items:center"><input type="number" min="0" name="s[finalWarningDays]" value="<?= (int) $s['finalWarningDays'] ?>" style="width:90px"> days before deletion</div></div>
    </div>
    <div class="card">
      <h2>Free trials</h2>
      <div class="f"><label>Default trial length</label><div style="display:flex;gap:8px;align-items:center"><input type="number" min="0" name="s[trialDays]" value="<?= (int) $s['trialDays'] ?>" style="width:90px"> days</div><small>Each package can set its own; this is the starting value for new packages.</small></div>
      <div class="f"><label>Remind before the trial ends</label><input type="text" name="s[trialRemindBefore]" value="<?= esc($s['trialRemindBefore']) ?>" placeholder="3,1"></div>
      <label class="check"><?= $chk('trialRequiresCard') ?><span>Ask for a card at sign-up<small>Card is saved and charged when the trial ends (Paystack).</small></span></label>
      <div class="f"><label>When a trial ends unpaid</label><select name="s[trialEndAction]">
        <option value="suspend" <?= $s['trialEndAction'] === 'suspend' ? 'selected' : '' ?>>Pause the workspace</option>
        <option value="downgrade" <?= $s['trialEndAction'] === 'downgrade' ? 'selected' : '' ?>>Move to the cheapest free package (if one exists)</option>
      </select></div>
    </div>
    <div class="card">
      <h2>Pricing page</h2>
      <div class="f"><label>Annual billing note</label><input type="text" name="s[annualDiscountNote]" value="<?= esc($s['annualDiscountNote']) ?>" placeholder="2 months free"><small>Shown next to the annual price.</small></div>
      <p class="muted small">Prices themselves are set per package under <a href="<?= site_url('packages') ?>">Packages</a>. Reminder wording is under <a href="<?= site_url('settings/templates') ?>">Email templates</a>.</p>
    </div>
  </div>
  <button class="btn">Save billing rules</button>
</form>
<?= $this->endSection() ?>
