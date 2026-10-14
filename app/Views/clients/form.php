<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
  $isEdit = ! empty($client['uid']);
  $val    = static fn ($k) => old($k) ?? ($client[$k] ?? '');
  $type   = old('type') ?? ($client['type'] ?? 'business');
?>

<div class="page-head">
  <div><h1><?= $isEdit ? 'Edit Client' : 'New Client' ?></h1><div class="sub"><?= $isEdit ? esc($client['name']) : 'Capture the client once — then pick them on every inspection and invoice.' ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url($isEdit ? 'clients/'.$client['uid'] : ($return ?: 'clients')) ?>">Cancel</a>
</div>

<form method="post" action="<?= site_url($isEdit ? 'clients/'.$client['uid'] : 'clients') ?>" x-data="{ type: '<?= esc($type, 'js') ?>' }">
  <?= csrf_field() ?>
  <?php if (! $isEdit && $return): ?><input type="hidden" name="return" value="<?= esc($return) ?>"><?php endif; ?>

  <div class="grid cols-2">
    <div class="card">
      <div class="card-h"><h3>Client</h3></div>
      <div class="card-b grid" style="gap:14px">
        <div style="display:flex;gap:8px">
          <label class="btn ghost sm" :style="type==='business' && 'background:var(--ink);color:#fff;border-color:var(--ink)'" style="cursor:pointer">
            <input type="radio" name="type" value="business" x-model="type" style="display:none"> Business
          </label>
          <label class="btn ghost sm" :style="type==='individual' && 'background:var(--ink);color:#fff;border-color:var(--ink)'" style="cursor:pointer">
            <input type="radio" name="type" value="individual" x-model="type" style="display:none"> Individual
          </label>
        </div>
        <label class="fld"><span x-text="type==='business' ? 'Business / trading name *' : 'Full name *'">Name *</span>
          <input type="text" name="name" value="<?= esc($val('name')) ?>" required maxlength="160">
        </label>
        <label class="fld" x-show="type==='business'"><span>Contact person</span>
          <input type="text" name="contact_person" value="<?= esc($val('contact_person')) ?>" maxlength="120">
        </label>
        <div class="grid cols-2" style="gap:14px">
          <label class="fld"><span>Contact number</span><input type="text" name="phone" value="<?= esc($val('phone')) ?>" placeholder="+27 82 000 0000"></label>
          <label class="fld"><span>Contact email</span><input type="email" name="email" value="<?= esc($val('email')) ?>"></label>
        </div>
        <label class="fld"><span>Physical address</span>
          <textarea name="address" rows="3"><?= esc($val('address')) ?></textarea>
        </label>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><h3>Billing &amp; Compliance</h3></div>
      <div class="card-b grid" style="gap:14px">
        <div class="grid cols-2" style="gap:14px">
          <label class="fld"><span>VAT number (optional)</span><input type="text" name="vat_number" value="<?= esc($val('vat_number')) ?>" class="mono"></label>
          <label class="fld" x-show="type==='business'"><span>Company reg. number</span><input type="text" name="registration_number" value="<?= esc($val('registration_number')) ?>" class="mono" placeholder="2020/123456/07"></label>
        </div>
        <label class="fld"><span>Accounts / billing email (if different)</span>
          <input type="email" name="billing_email" value="<?= esc($val('billing_email')) ?>" placeholder="accounts@client.co.za">
        </label>
        <label class="fld"><span>Payment terms (days) — blank uses the default of <?= (int) setting('Billing.paymentTermsDays') ?></span>
          <input type="number" name="payment_terms_days" min="0" max="365" value="<?= esc((string) $val('payment_terms_days')) ?>">
        </label>
        <label style="display:flex;gap:8px;align-items:center;font-size:13.5px">
          <input type="checkbox" name="monthly_statements" value="1" <?= (old('monthly_statements') ?? ($client['monthly_statements'] ?? 1)) ? 'checked' : '' ?>>
          Email this client a monthly statement with open invoices
        </label>
        <label class="fld"><span>Internal notes</span>
          <textarea name="notes" rows="3" placeholder="Not shown to the client"><?= esc($val('notes')) ?></textarea>
        </label>
      </div>
    </div>
  </div>

  <div style="margin-top:18px;display:flex;gap:10px">
    <button class="btn" type="submit"><?= $isEdit ? 'Save Changes' : ($return ? 'Save &amp; Continue →' : 'Save Client') ?></button>
  </div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?= $this->endSection() ?>
