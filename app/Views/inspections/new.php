<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>New Inspection</h1><div class="sub">Choose the client, a checklist and the vehicle to inspect.</div></div>
</div>

<form method="post" action="<?= site_url('inspections') ?>" x-data="{ mode: '<?= empty($vehicles) ? 'new' : 'existing' ?>' }">
  <?= csrf_field() ?>

  <div class="grid <?= feature('billing') ? 'cols-2' : '' ?>" style="margin-bottom:16px">
    <div class="card" style="overflow:visible">
      <div class="card-h"><h3>Client</h3><span class="pill" style="margin-left:auto">Required</span></div>
      <div class="card-b">
        <?php if (empty($clients)): ?>
          <div class="empty" style="padding:6px 0">
            No clients yet. Every inspection is done for a client — <a href="<?= site_url('clients/new?return=inspections/new') ?>"><b>add your first client</b></a> to continue.
          </div>
        <?php else: ?>
          <?= view('partials/client_picker', ['clients' => $clients, 'selectedId' => $clientId, 'returnTo' => 'inspections/new'], ['saveData' => false]) ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if (feature('billing')): ?>
    <div class="card">
      <div class="card-h"><h3>Inspection Charge</h3><span class="sub" style="margin-left:auto;font-size:11.5px;color:var(--ink-3)">Invoiced when the inspection is done</span></div>
      <div class="card-b grid cols-2" style="gap:12px">
        <label class="fld" style="grid-column:1/-1"><span>Description</span>
          <input type="text" name="charge_description" value="<?= esc(old('charge_description') ?? setting('Billing.defaultChargeDescription')) ?>">
        </label>
        <label class="fld"><span>Amount (<?= esc(setting('Pay.currency') ?: 'ZAR') ?>, <?= setting('Billing.vatRegistered') ? 'excl. VAT' : 'no VAT' ?>)</span>
          <input type="number" name="charge_amount" step="0.01" min="0" value="<?= esc(old('charge_amount') ?? number_format((float) setting('Billing.defaultInspectionFee'), 2, '.', '')) ?>">
        </label>
        <div class="sub" style="align-self:end;font-size:12px;color:var(--ink-3);padding-bottom:10px">
          <?php if (setting('Billing.vatRegistered')): ?>+ <?= esc(rtrim(rtrim(number_format((float) setting('Billing.vatRate'), 2), '0'), '.')) ?>% VAT added on the invoice.<?php else: ?>You can add extra line items on the invoice.<?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="grid cols-2">
    <div class="card">
      <div class="card-h"><h3>Checklist Template</h3></div>
      <div class="card-b grid" style="gap:14px">
        <label class="fld"><span>Template</span>
          <select name="template_id" required>
            <?php foreach ($templates as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $t['is_default'] ? 'selected' : '' ?>><?= esc($t['name']) ?> (v<?= esc($t['version']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="fld"><span>Inspection Date</span>
          <input type="date" name="inspection_date" value="<?= date('Y-m-d') ?>">
        </label>
      </div>
    </div>

    <div class="card">
      <div class="card-h">
        <h3>Vehicle</h3>
        <div style="margin-left:auto;display:flex;gap:6px">
          <?php if (! empty($vehicles)): ?>
          <button type="button" class="btn ghost sm" :style="mode==='existing' && 'background:var(--ink);color:#fff;border-color:var(--ink)'" @click="mode='existing'">Existing</button>
          <?php endif; ?>
          <button type="button" class="btn ghost sm" :style="mode==='new' && 'background:var(--ink);color:#fff;border-color:var(--ink)'" @click="mode='new'">New</button>
        </div>
      </div>
      <div class="card-b">
        <div x-show="mode==='existing'">
          <label class="fld"><span>Select Vehicle</span>
            <select name="vehicle_id" x-bind:disabled="mode!=='existing'">
              <option value="0">— choose —</option>
              <?php foreach ($vehicles as $v): ?>
                <option value="<?= $v['id'] ?>"><?= esc(trim(($v['year']?:'').' '.($v['make']?:'').' '.($v['model']?:'').' · '.($v['plate']?:$v['vin']?:''))) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <div x-show="mode==='new'" class="grid cols-2" style="gap:12px">
          <label class="fld"><span>Make</span><input type="text" name="make" placeholder="Volkswagen"></label>
          <label class="fld"><span>Model</span><input type="text" name="model" placeholder="Polo"></label>
          <label class="fld"><span>Year</span><input type="number" name="year" placeholder="2017"></label>
          <label class="fld"><span>Colour</span><input type="text" name="colour" placeholder="Silver"></label>
          <label class="fld"><span>Registration</span><input type="text" name="plate" placeholder="CA 123-456"></label>
          <label class="fld"><span>VIN</span><input type="text" name="vin" class="mono" placeholder="17-char VIN"></label>
          <label class="fld"><span>Odometer (km)</span><input type="text" name="odometer" placeholder="144 529"></label>
          <label class="fld"><span>Dealer / Seller</span><input type="text" name="seller" placeholder="Wheelsturn"></label>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:18px;display:flex;gap:10px">
    <button class="btn" type="submit" <?= empty($clients) ? 'disabled title="Add a client first"' : '' ?>>Create &amp; Start Capturing →</button>
    <a class="btn ghost" href="<?= site_url('dashboard') ?>">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?= $this->endSection() ?>
