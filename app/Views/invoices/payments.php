<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
  $isAdmin = auth()->user()->inGroup('admin');
  $openInv = array_values(array_map(static fn ($i) => [
      'id'     => (int) $i['id'],
      'client' => (int) $i['client_id'],
      'label'  => $i['invoice_no'] . ' · due ' . $i['due_date'] . ' · ' . money(\App\Models\InvoiceModel::balance($i)) . ' outstanding',
      'due'    => \App\Models\InvoiceModel::balance($i),
  ], array_filter($open, static fn ($i) => in_array($i['status'], \App\Models\InvoiceModel::OPEN, true))));
?>

<div class="page-head">
  <div><h1>Payments</h1><div class="sub">Record money received. It's applied to the chosen invoice, then the client's oldest open invoices, then held as credit.</div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('invoices') ?>">Invoices</a>
</div>

<style>.pay-grid{grid-template-columns:minmax(0,380px) minmax(0,1fr);align-items:start}@media(max-width:860px){.pay-grid{grid-template-columns:1fr}}</style>
<div class="grid pay-grid">
  <div class="card" style="overflow:visible">
    <div class="card-h"><h3>Record a payment</h3></div>
    <div class="card-b">
      <form method="post" action="<?= site_url('invoices/payments') ?>" class="grid" style="gap:12px"
            x-data="{ client: <?= (int) ($clientId ?? 0) ?>, invoice: '<?= (int) ($invoiceId ?? 0) ?: '' ?>', all: <?= esc(json_encode($openInv), 'attr') ?>,
                      get list(){ return this.all.filter(i => i.client === this.client) },
                      get suggested(){ const i = this.all.find(x => String(x.id) === String(this.invoice)); return i ? i.due.toFixed(2) : '' } }"
            @client-picked.window="client = $event.detail.id; if (!list.find(i => String(i.id) === String(invoice))) invoice = ''">
        <?= csrf_field() ?>
        <label class="fld"><span>Client</span></label>
        <?= view('partials/client_picker', ['clients' => $clients, 'selectedId' => $clientId, 'returnTo' => 'invoices/payments'], ['saveData' => false]) ?>
        <label class="fld"><span>Apply to invoice</span>
          <select name="invoice_id" x-model="invoice">
            <option value="">Oldest open invoices first (auto)</option>
            <template x-for="i in list" :key="i.id"><option :value="i.id" x-text="i.label" :selected="String(i.id) === String(invoice)"></option></template>
          </select>
        </label>
        <div class="grid cols-2" style="gap:10px">
          <label class="fld"><span>Amount</span><input type="number" name="amount" step="0.01" min="0.01" required :placeholder="suggested"></label>
          <label class="fld"><span>Date received</span><input type="date" name="paid_at" value="<?= date('Y-m-d') ?>"></label>
        </div>
        <label class="fld"><span>Method</span>
          <select name="method"><?php foreach (\App\Models\PaymentModel::METHODS as $k => $l): ?><option value="<?= $k ?>"><?= esc($l) ?></option><?php endforeach; ?></select>
        </label>
        <label class="fld"><span>Reference</span><input type="text" name="reference" placeholder="Bank statement ref / receipt no"></label>
        <label class="fld"><span>Notes</span><input type="text" name="notes"></label>
        <button class="btn">Record payment</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-h"><h3>Recent payments</h3></div>
    <?php if (empty($payments)): ?>
      <div class="empty" style="padding:22px 18px">No payments recorded yet.</div>
    <?php else: ?>
    <div class="tbl-scroll">
    <table class="tbl">
      <thead><tr><th>Date</th><th>Client</th><th>Method</th><th>Reference</th><th>Applied to</th><th style="text-align:right">Amount</th><?php if ($isAdmin): ?><th></th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= esc($p['paid_at']) ?></td>
          <td><?= esc($p['client_name'] ?? '—') ?></td>
          <td><?= esc(\App\Models\PaymentModel::METHODS[$p['method']] ?? $p['method']) ?></td>
          <td class="mono" style="font-size:12px"><?= esc($p['reference'] ?: '—') ?></td>
          <td><?= $p['invoice_no'] ? '<a class="mono" href="'.site_url('invoices/'.$p['invoice_uid']).'">'.esc($p['invoice_no']).'</a>' : '<span class="sub">Credit on account</span>' ?></td>
          <td style="text-align:right;font-weight:600;color:var(--ok)"><?= esc(money($p['amount'])) ?></td>
          <?php if ($isAdmin): ?>
          <td style="text-align:right"><form method="post" action="<?= site_url('payments/'.$p['id'].'/delete') ?>" onsubmit="return confirm('Remove this payment?')"><?= csrf_field() ?><button class="btn ghost sm">Remove</button></form></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?= $this->endSection() ?>
