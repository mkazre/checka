<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<style>
  .sg{position:absolute;z-index:40;left:10px;right:10px;top:calc(100% - 4px);background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 14px 40px rgba(16,23,29,.14);padding:6px;max-height:280px;overflow-y:auto}
  .sg-opt{padding:8px 10px;border-radius:7px;cursor:pointer;display:flex;flex-direction:column;gap:2px}
  .sg-opt.on{background:#2d2d2d0d}
  .sg-meta{font-size:12px;color:var(--ink-3)}
  .sg-new{padding:9px 10px;font-size:12.5px;color:var(--ink-3)}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
  use App\Models\PurchaseOrderModel;

  $isEdit = $po !== null;
  $val    = static fn ($k, $def = '') => old($k) ?? ($po[$k] ?? $def);
  $rows   = array_map(static fn ($i) => [
      'description' => (string) $i['description'],
      'part_no'     => (string) ($i['part_no'] ?? ''),
      'qty'         => (float) $i['qty'],
      'unit_price'  => $i['unit_price'] === null ? '' : (string) (float) $i['unit_price'],
  ], $items);
  $vehList = array_map(static fn ($v) => [
      'id' => (int) $v['id'], 'label' => trim(($v['year'] ?: '') . ' ' . ($v['make'] ?: '') . ' ' . ($v['model'] ?: '') . ' · ' . ($v['plate'] ?: $v['vin'] ?: '')),
      'veh_make' => (string) $v['make'], 'veh_model' => trim(($v['model'] ?: '') . ' ' . ($v['derivative'] ?? '')), 'veh_year' => (string) $v['year'],
      'veh_vin' => (string) $v['vin'], 'veh_plate' => (string) $v['plate'], 'veh_colour' => (string) $v['colour'],
      'veh_engine' => trim(($v['power_displacement'] ?? '') . (! empty($v['engine_number']) ? ' · ' . $v['engine_number'] : '')),
      'veh_odometer' => (string) $v['odometer'],
  ], $vehicles);
  $veh = [];
  foreach (array_keys(PurchaseOrderModel::VEHICLE) as $k) { $veh[$k] = (string) $val($k); }
  $showPricing = (bool) (old('show_pricing') ?? ($po['show_pricing'] ?? 0));
  $showVehicle = (bool) (old('show_vehicle') ?? ($po['show_vehicle'] ?? 1));
  $vatRate     = (float) $val('vat_rate', setting('Purchasing.defaultVatRate') ?: 15);
?>

<div class="page-head">
  <div><h1><?= $isEdit ? 'Edit ' . esc($po['po_no']) : 'New Purchase Order' ?></h1><div class="sub">Pick the vendor, list what you need. Prices and vehicle details are optional — you choose what goes on the PDF.</div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url($isEdit ? 'purchase-orders/'.$po['uid'] : 'purchase-orders') ?>">Cancel</a>
</div>

<form method="post" action="<?= site_url($isEdit ? 'purchase-orders/'.$po['uid'] : 'purchase-orders') ?>"
      x-data="poForm(<?= esc(json_encode($rows), 'attr') ?>, <?= $vatRate ?>, <?= $showPricing ? 'true' : 'false' ?>, <?= esc(json_encode($veh), 'attr') ?>, <?= esc(json_encode($vehList), 'attr') ?>)">
  <?= csrf_field() ?>

  <div class="grid cols-2">
    <div class="card" style="overflow:visible">
      <div class="card-h"><h3>Order to (vendor)</h3><span class="pill" style="margin-left:auto">Required</span></div>
      <div class="card-b">
        <?php if (empty($vendors)): ?>
          <div class="empty" style="padding:6px 0">No vendors yet — <a href="<?= site_url('vendors/new?return=purchase-orders/new') ?>"><b>add your first vendor</b></a> to continue.</div>
        <?php else: ?>
          <?= view('partials/client_picker', ['clients' => $vendors, 'selectedId' => $vendorId, 'name' => 'vendor_id', 'noun' => 'vendor',
              'addUrl' => site_url('vendors/new?return=purchase-orders/new')], ['saveData' => false]) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-h"><h3>Order details</h3></div>
      <div class="card-b grid" style="gap:12px">
        <div class="grid cols-2" style="gap:12px">
          <label class="fld"><span>Order date</span><input type="date" name="order_date" value="<?= esc($val('order_date', date('Y-m-d'))) ?>" required></label>
          <label class="fld"><span>Our reference (optional)</span><input type="text" name="reference" value="<?= esc($val('reference')) ?>" placeholder="Job / quote no"></label>
        </div>
        <label class="fld"><span>Order description</span><input type="text" name="description" value="<?= esc($val('description')) ?>" placeholder="e.g. Repairs for Golf — WVWZZZAUZHW316715"></label>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-h">
      <h3>Vehicle (optional)</h3>
      <label style="margin-left:auto;display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" name="show_vehicle" value="1" <?= $showVehicle ? 'checked' : '' ?>> Show on PDF</label>
    </div>
    <div class="card-b grid" style="gap:12px">
      <label class="fld" style="max-width:520px"><span>Fill from a vehicle on record</span>
        <select name="vehicle_id" x-model="vehicleId" @change="fillVehicle()">
          <option value="">— type the details below, or pick a vehicle —</option>
          <template x-for="v in vehicles" :key="v.id"><option :value="v.id" x-text="v.label" :selected="String(v.id) === String(vehicleId)"></option></template>
        </select>
      </label>
      <div class="grid cols-4" style="gap:12px">
        <?php foreach (PurchaseOrderModel::VEHICLE as $k => $l): ?>
          <label class="fld"><span><?= $l ?></span><input type="text" name="<?= $k ?>" x-model="veh.<?= $k ?>" class="<?= $k === 'veh_vin' ? 'mono' : '' ?>"></label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-h">
      <h3>Items</h3>
      <label style="margin-left:auto;display:flex;gap:6px;align-items:center;font-size:13px;font-weight:600"><input type="checkbox" name="show_pricing" value="1" x-model="pricing"> Include pricing</label>
    </div>
    <div style="overflow:visible">
    <table class="tbl">
      <thead><tr>
        <th style="width:44%">Description</th><th style="width:14%">Part no</th><th style="width:9%">Qty</th>
        <th style="width:14%" x-show="pricing">Unit price (excl.)</th><th style="width:13%;text-align:right" x-show="pricing">Amount incl.</th><th></th>
      </tr></thead>
      <tbody>
        <template x-for="(r, i) in rows" :key="i">
          <tr>
            <td style="position:relative" @click.outside="r.open=false">
              <input type="text" name="item_description[]" x-model="r.description" placeholder="Type 3+ letters to search saved items…" required autocomplete="off"
                     @input.debounce.250ms="lookup(r)" @focus="r.description.length >= 3 && lookup(r)"
                     @keydown.arrow-down.prevent="r.open && (r.hi = Math.min(r.hi + 1, r.sugg.length - 1))"
                     @keydown.arrow-up.prevent="r.open && (r.hi = Math.max(r.hi - 1, 0))"
                     @keydown.enter="if (r.open && r.sugg[r.hi]) { $event.preventDefault(); pick(r, r.sugg[r.hi]); }"
                     @keydown.escape="r.open=false">
              <div class="sg" x-show="r.open" x-cloak>
                <template x-for="(s, si) in r.sugg" :key="s.id">
                  <div class="sg-opt" :class="{on: si === r.hi}" @mouseenter="r.hi = si" @click="pick(r, s)">
                    <b x-text="s.name"></b>
                    <span class="sg-meta" x-text="[s.part_no ? 'Part ' + s.part_no : '', s.price !== '' ? fmt(+s.price) + ' excl.' : 'no price', 'used ' + s.used + '×'].filter(Boolean).join(' · ')"></span>
                  </div>
                </template>
                <div class="sg-new" x-show="!r.sugg.length">New item — it'll be saved to Items when you save this PO.</div>
              </div>
            </td>
            <td><input type="text" name="item_part[]" x-model="r.part_no" class="mono"></td>
            <td><input type="number" name="item_qty[]" x-model.number="r.qty" step="0.01" min="0"></td>
            <td x-show="pricing"><input type="number" name="item_price[]" x-model="r.unit_price" step="0.01" placeholder="TBA"></td>
            <td x-show="pricing" style="text-align:right;font-weight:600" x-text="r.unit_price === '' ? '—' : fmt(incl(r))"></td>
            <td style="text-align:right"><button type="button" class="btn ghost sm" @click="rows.splice(i,1)" x-show="rows.length > 1" title="Remove line">✕</button></td>
          </tr>
        </template>
      </tbody>
    </table>
    </div>
    <div class="card-b" style="display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;border-top:1px solid var(--line)">
      <button type="button" class="btn ghost sm" @click="rows.push(blank())">+ Add item</button>
      <table style="min-width:280px;font-size:14px" x-show="pricing">
        <tr><td style="color:var(--ink-3);padding:3px 14px 3px 0">Nett total</td><td style="text-align:right" x-text="fmt(nett)"></td></tr>
        <tr><td style="color:var(--ink-3);padding:3px 14px 3px 0">VAT @ <input type="number" name="vat_rate" x-model.number="vat" step="0.01" min="0" max="100" style="width:70px;padding:3px 6px;font-size:13px">%</td><td style="text-align:right" x-text="fmt(vatAmt)"></td></tr>
        <tr><td style="padding:6px 14px 3px 0;font-weight:700;border-top:1px solid var(--line)">Order total</td><td style="text-align:right;font-weight:700;font-size:17px;border-top:1px solid var(--line)" x-text="fmt(total)"></td></tr>
        <tr x-show="unpriced > 0"><td colspan="2" style="font-size:12px;color:var(--ink-3);padding-top:4px" x-text="unpriced + ' item(s) still to be priced — shown as TBA'"></td></tr>
      </table>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-h"><h3>Notes to vendor</h3></div>
    <div class="card-b"><textarea name="notes" rows="3" placeholder="Optional — delivery instructions, collection details… printed on the PO"><?= esc($val('notes')) ?></textarea></div>
  </div>

  <div style="margin-top:18px"><button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create purchase order' ?></button></div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
  function poForm(rows, vat, pricing, veh, vehicles) {
    const blank = () => ({description: '', part_no: '', qty: 1, unit_price: '', sugg: [], open: false, hi: 0});
    return {
      rows: rows.map(r => Object.assign(blank(), r)), blank, vat, pricing, veh, vehicles, vehicleId: '<?= esc((string) $val('vehicle_id'), 'js') ?>',
      async lookup(r) {
        const q = (r.description || '').trim();
        if (q.length < 3) { r.open = false; r.sugg = []; return; }
        try {
          const res = await fetch('<?= site_url('items/suggest') ?>?q=' + encodeURIComponent(q), {headers: {'X-Requested-With': 'XMLHttpRequest'}});
          r.sugg = await res.json();
        } catch (e) { r.sugg = []; }
        // Hide the list once the typed text exactly matches a saved item.
        r.open = !(r.sugg.length === 1 && r.sugg[0].name.toLowerCase() === q.toLowerCase());
        r.hi = 0;
      },
      pick(r, s) {
        r.description = s.name;
        if (s.part_no) r.part_no = s.part_no;
        if (s.price !== '') r.unit_price = s.price;   // still editable on the line
        r.open = false;
      },
      ex(r) { return r.unit_price === '' || r.unit_price === null ? 0 : Math.round((+r.qty || 0) * (+r.unit_price || 0) * 100) / 100; },
      incl(r) { return Math.round(this.ex(r) * (1 + (+this.vat || 0) / 100) * 100) / 100; },
      get nett() { return Math.round(this.rows.reduce((s, r) => s + this.ex(r), 0) * 100) / 100; },
      get vatAmt() { return Math.round(this.nett * (+this.vat || 0)) / 100; },
      get total() { return Math.round((this.nett + this.vatAmt) * 100) / 100; },
      get unpriced() { return this.rows.filter(r => r.description && (r.unit_price === '' || r.unit_price === null)).length; },
      fillVehicle() {
        const v = this.vehicles.find(x => String(x.id) === String(this.vehicleId)); if (!v) return;
        Object.keys(this.veh).forEach(k => { if (v[k] !== undefined) this.veh[k] = v[k] || ''; });
      },
      fmt(n) { return '<?= esc(explode(' ', money(0))[0], 'js') ?> ' + (n || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).replace(/,/g, ' '); },
    };
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?= $this->endSection() ?>
