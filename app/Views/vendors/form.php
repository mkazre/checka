<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
  $isEdit = ! empty($vendor['uid']);
  $val    = static fn ($k) => old($k) ?? ($vendor[$k] ?? '');
?>

<div class="page-head">
  <div><h1><?= $isEdit ? 'Edit Vendor' : 'New Vendor' ?></h1><div class="sub"><?= $isEdit ? esc($vendor['name']) : 'Capture the supplier once — then pick them on every purchase order.' ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url($isEdit ? 'vendors/'.$vendor['uid'] : ($return ?: 'vendors')) ?>">Cancel</a>
</div>

<form method="post" action="<?= site_url($isEdit ? 'vendors/'.$vendor['uid'] : 'vendors') ?>">
  <?= csrf_field() ?>
  <?php if (! $isEdit && $return): ?><input type="hidden" name="return" value="<?= esc($return) ?>"><?php endif; ?>
  <div class="grid cols-2">
    <div class="card">
      <div class="card-h"><h3>Supplier</h3></div>
      <div class="card-b grid" style="gap:14px">
        <label class="fld"><span>Company / trading name *</span><input type="text" name="name" value="<?= esc($val('name')) ?>" required maxlength="160" placeholder="e.g. Bidvest McCarthy Audi Centre West Rand"></label>
        <label class="fld"><span>Contact person</span><input type="text" name="contact_person" value="<?= esc($val('contact_person')) ?>"></label>
        <div class="grid cols-2" style="gap:14px">
          <label class="fld"><span>Contact number</span><input type="text" name="phone" value="<?= esc($val('phone')) ?>"></label>
          <label class="fld"><span>Orders email</span><input type="email" name="email" value="<?= esc($val('email')) ?>" placeholder="parts@supplier.co.za"></label>
        </div>
        <label class="fld"><span>Physical address</span><textarea name="address" rows="3"><?= esc($val('address')) ?></textarea></label>
      </div>
    </div>
    <div class="card">
      <div class="card-h"><h3>Account &amp; Compliance</h3></div>
      <div class="card-b grid" style="gap:14px">
        <label class="fld"><span>Our account number with them</span><input type="text" name="account_number" value="<?= esc($val('account_number')) ?>" class="mono" placeholder="e.g. W0002016"></label>
        <div class="grid cols-2" style="gap:14px">
          <label class="fld"><span>VAT number</span><input type="text" name="vat_number" value="<?= esc($val('vat_number')) ?>" class="mono"></label>
          <label class="fld"><span>Company reg. number</span><input type="text" name="registration_number" value="<?= esc($val('registration_number')) ?>" class="mono"></label>
        </div>
        <label class="fld"><span>Internal notes</span><textarea name="notes" rows="4" placeholder="Not shown to the vendor"><?= esc($val('notes')) ?></textarea></label>
      </div>
    </div>
  </div>
  <div style="margin-top:18px"><button class="btn" type="submit"><?= $isEdit ? 'Save Changes' : ($return ? 'Save &amp; Continue →' : 'Save Vendor') ?></button></div>
</form>

<?= $this->endSection() ?>
