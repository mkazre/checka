<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
  $isEdit  = $invoice !== null;
  $vatRate = $isEdit ? (float) $invoice['vat_rate'] : (setting('Billing.vatRegistered') ? (float) setting('Billing.vatRate') : 0.0);
  $rows    = array_map(static fn ($i) => [
      'description' => (string) $i['description'],
      'qty'         => (float) $i['qty'],
      'unit_price'  => (float) $i['unit_price'],
  ], $items ?: [['description' => '', 'qty' => 1, 'unit_price' => 0]]);
  $issue = old('issue_date') ?? ($invoice['issue_date'] ?? date('Y-m-d'));
  $due   = old('due_date') ?? ($invoice['due_date'] ?? '');
  $clientLocked = $isEdit && (float) $invoice['amount_paid'] > 0;
?>

<div class="page-head">
  <div><h1><?= $isEdit ? 'Edit ' . esc($invoice['invoice_no']) : 'New Invoice' ?></h1><div class="sub"><?= $isEdit ? 'Changes recalculate totals and the client\'s balance.' : 'Prices are entered ' . ($vatRate > 0 ? 'excluding VAT — VAT is added below.' : 'as final amounts (not VAT registered).') ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url($isEdit ? 'invoices/'.$invoice['uid'] : 'invoices') ?>">Cancel</a>
</div>

<form method="post" action="<?= site_url($isEdit ? 'invoices/'.$invoice['uid'] : 'invoices') ?>"
      x-data="invoiceForm(<?= esc(json_encode($rows), 'attr') ?>, <?= $vatRate ?>)">
  <?= csrf_field() ?>

  <div class="grid cols-2">
    <div class="card" style="overflow:visible">
      <div class="card-h"><h3>Bill to</h3></div>
      <div class="card-b">
        <?php if ($clientLocked): ?>
          <input type="hidden" name="client_id" value="<?= (int) $clientId ?>">
          <?php foreach ($clients as $c) { if ((int) $c['id'] === (int) $clientId) { echo '<b>' . esc($c['name']) . '</b>'; } } ?>
          <div class="sub" style="font-size:12px;color:var(--ink-3);margin-top:4px">The client can't change once payments are recorded against this invoice.</div>
        <?php else: ?>
          <?= view('partials/client_picker', ['clients' => $clients, 'selectedId' => (int) (old('client_id') ?: $clientId), 'returnTo' => 'invoices/new'], ['saveData' => false]) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-h"><h3>Dates</h3></div>
      <div class="card-b grid cols-2" style="gap:12px">
        <label class="fld"><span>Issue date</span><input type="date" name="issue_date" value="<?= esc($issue) ?>" required></label>
        <label class="fld"><span>Due date <?= $isEdit ? '' : '(blank = client terms)' ?></span><input type="date" name="due_date" value="<?= esc($due) ?>"></label>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-h"><h3>Line items</h3></div>
    <div class="tbl-scroll">
    <table class="tbl">
      <thead><tr><th style="width:56%">Description</th><th style="width:10%">Qty</th><th style="width:16%">Unit price</th><th style="width:14%;text-align:right">Amount</th><th></th></tr></thead>
      <tbody>
        <template x-for="(r, i) in rows" :key="i">
          <tr>
            <td><input type="text" name="item_description[]" x-model="r.description" placeholder="e.g. Vehicle condition inspection" required></td>
            <td><input type="number" name="item_qty[]" x-model.number="r.qty" step="0.01" min="0"></td>
            <td><input type="number" name="item_price[]" x-model.number="r.unit_price" step="0.01"></td>
            <td style="text-align:right;font-weight:600" x-text="fmt(line(r))"></td>
            <td style="text-align:right"><button type="button" class="btn ghost sm" @click="rows.splice(i,1)" x-show="rows.length > 1" title="Remove line">✕</button></td>
          </tr>
        </template>
      </tbody>
    </table>
    </div>
    <div class="card-b" style="display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;border-top:1px solid var(--line)">
      <button type="button" class="btn ghost sm" @click="rows.push({description:'', qty:1, unit_price:0})">+ Add line</button>
      <table style="min-width:260px;font-size:14px">
        <tr><td style="color:var(--ink-3);padding:3px 14px 3px 0">Subtotal</td><td style="text-align:right" x-text="fmt(subtotal)"></td></tr>
        <tr x-show="vat > 0"><td style="color:var(--ink-3);padding:3px 14px 3px 0">VAT @ <span x-text="vat"></span>%</td><td style="text-align:right" x-text="fmt(vatAmt)"></td></tr>
        <tr><td style="padding:6px 14px 3px 0;font-weight:700;border-top:1px solid var(--line)">Total</td><td style="text-align:right;font-weight:700;font-size:17px;border-top:1px solid var(--line)" x-text="fmt(total)"></td></tr>
      </table>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-h"><h3>Notes to client</h3></div>
    <div class="card-b"><textarea name="notes" rows="3" placeholder="Optional — printed on the invoice"><?= esc(old('notes') ?? ($invoice['notes'] ?? '')) ?></textarea></div>
  </div>

  <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap">
    <?php if (! $isEdit || $invoice['status'] === 'draft'): ?>
      <button class="btn ghost" type="submit">Save as draft</button>
      <button class="btn" type="submit" name="issue" value="1">Save &amp; issue</button>
    <?php else: ?>
      <button class="btn" type="submit">Save changes</button>
    <?php endif; ?>
  </div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
  function invoiceForm(rows, vat) {
    return {
      rows, vat,
      line(r) { return Math.round((+r.qty || 0) * (+r.unit_price || 0) * 100) / 100; },
      get subtotal() { return Math.round(this.rows.reduce((s, r) => s + this.line(r), 0) * 100) / 100; },
      get vatAmt() { return Math.round(this.subtotal * this.vat) / 100; },
      get total() { return Math.round((this.subtotal + this.vatAmt) * 100) / 100; },
      fmt(n) { return '<?= esc(explode(' ', money(0))[0], 'js') ?> ' + (n || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).replace(/,/g, ' '); },
    };
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?= $this->endSection() ?>
