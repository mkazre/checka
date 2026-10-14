<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<style>
  [x-cloak]{display:none!important}
  .kv{display:grid;grid-template-columns:150px 1fr;gap:6px 12px;font-size:13.5px}
  .kv .k{font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);padding-top:2px}
  .tabs{display:flex;gap:4px;border-bottom:1px solid var(--line);margin:22px 0 14px;flex-wrap:wrap}
  .tabs button{background:none;border:none;border-bottom:2px solid transparent;padding:9px 14px;font:600 13.5px var(--display);color:var(--ink-3);cursor:pointer}
  .tabs button.on{color:var(--ink);border-bottom-color:var(--brand)}
  .ageing{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}
  .ageing div{border:1px solid var(--line);border-radius:9px;padding:10px;text-align:center}
  .ageing .k{font-family:var(--mono);font-size:9.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--ink-3)}
  .ageing .v{font-weight:700;margin-top:3px}
  @media(max-width:700px){.ageing{grid-template-columns:repeat(2,1fr)}.kv{grid-template-columns:1fr}}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $c = $client; ?>

<div class="page-head">
  <div>
    <h1><?= esc($c['name']) ?></h1>
    <div class="sub"><?= $c['type'] === 'individual' ? 'Individual' : 'Business' ?> · Account <?= esc(strtoupper(substr($c['uid'], 0, 8))) ?></div>
  </div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('clients/'.$c['uid'].'/edit') ?>">Edit</a>
  <?php if (feature('billing')): ?>
    <a class="btn ghost" href="<?= site_url('invoices/new?client='.$c['uid']) ?>">New Invoice</a>
    <a class="btn ghost" href="<?= site_url('invoices/payments?client='.$c['uid']) ?>">Record Payment</a>
  <?php endif; ?>
  <a class="btn" href="<?= site_url('inspections/new?client='.$c['uid']) ?>">New Inspection</a>
</div>

<div class="grid <?= $billing ? 'cols-2' : '' ?>">
  <div class="card">
    <div class="card-h"><h3>Details</h3></div>
    <div class="card-b kv">
      <?php if ($c['contact_person']): ?><div class="k">Contact</div><div><?= esc($c['contact_person']) ?></div><?php endif; ?>
      <div class="k">Phone</div><div><?= esc($c['phone'] ?: '—') ?></div>
      <div class="k">Email</div><div><?= $c['email'] ? '<a href="mailto:'.esc($c['email']).'">'.esc($c['email']).'</a>' : '—' ?></div>
      <?php if ($c['billing_email']): ?><div class="k">Accounts email</div><div><?= esc($c['billing_email']) ?></div><?php endif; ?>
      <div class="k">Address</div><div><?= $c['address'] ? nl2br(esc($c['address'])) : '—' ?></div>
      <div class="k">VAT no</div><div class="mono"><?= esc($c['vat_number'] ?: '—') ?></div>
      <?php if ($c['registration_number']): ?><div class="k">Reg no</div><div class="mono"><?= esc($c['registration_number']) ?></div><?php endif; ?>
      <div class="k">Terms</div><div><?= (int) ($c['payment_terms_days'] ?? 0) ?: (int) setting('Billing.paymentTermsDays') ?> days<?= $c['payment_terms_days'] === null ? ' (default)' : '' ?></div>
      <div class="k">Monthly statement</div><div><?= $c['monthly_statements'] ? 'Yes' : 'No' ?><?= $c['last_statement_period'] ? ' · last sent for '.esc($c['last_statement_period']) : '' ?></div>
      <?php if ($c['notes']): ?><div class="k">Notes</div><div style="color:var(--ink-2)"><?= nl2br(esc($c['notes'])) ?></div><?php endif; ?>
    </div>
  </div>

  <?php if ($billing): $bal = $billing['balance']; $ag = $billing['ageing']; ?>
  <div class="card">
    <div class="card-h"><h3>Account</h3><a class="btn ghost sm" style="margin-left:auto" href="<?= site_url('pay/account/'.$c['uid']) ?>" target="_blank">Client pay page ↗</a></div>
    <div class="card-b">
      <div class="kpi" style="border:none;padding:0 0 14px">
        <div class="k"><?= $bal < 0 ? 'Credit balance' : 'Balance outstanding' ?></div>
        <div class="v" style="color:<?= $bal > 0 ? 'var(--major)' : 'var(--ok)' ?>"><?= esc(money(abs($bal))) ?></div>
      </div>
      <div class="ageing">
        <div><div class="k">Current</div><div class="v"><?= esc(money($ag['current'])) ?></div></div>
        <div><div class="k">1–30</div><div class="v"><?= esc(money($ag['d30'])) ?></div></div>
        <div><div class="k">31–60</div><div class="v"><?= esc(money($ag['d60'])) ?></div></div>
        <div><div class="k">61–90</div><div class="v" style="<?= $ag['d90'] > 0 ? 'color:var(--major)' : '' ?>"><?= esc(money($ag['d90'])) ?></div></div>
        <div><div class="k">90+</div><div class="v" style="<?= $ag['d90plus'] > 0 ? 'color:var(--major)' : '' ?>"><?= esc(money($ag['d90plus'])) ?></div></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<div x-data="{ tab: '<?= $billing ? 'statement' : 'inspections' ?>' }">
  <div class="tabs">
    <?php if ($billing): ?>
      <button type="button" :class="{on: tab==='statement'}" @click="tab='statement'">Statement</button>
      <button type="button" :class="{on: tab==='invoices'}" @click="tab='invoices'">Invoices (<?= count($billing['invoices']) ?>)</button>
      <button type="button" :class="{on: tab==='payments'}" @click="tab='payments'">Payments (<?= count($billing['payments']) ?>)</button>
    <?php endif; ?>
    <button type="button" :class="{on: tab==='inspections'}" @click="tab='inspections'">Inspections (<?= count($inspections) ?>)</button>
  </div>

  <?php if ($billing): $st = $billing['statement']; ?>
  <div x-show="tab==='statement'" class="card">
    <div class="card-h" style="flex-wrap:wrap;gap:8px">
      <h3>Statement</h3>
      <form method="get" action="<?= site_url('clients/'.$c['uid']) ?>" style="margin-left:auto;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
        <input type="date" name="from" value="<?= esc($billing['from']) ?>" style="padding:6px 8px;font-size:13px;width:auto">
        <span class="sub">to</span>
        <input type="date" name="to" value="<?= esc($billing['to']) ?>" style="padding:6px 8px;font-size:13px;width:auto">
        <button class="btn ghost sm">Show</button>
        <a class="btn ghost sm" target="_blank" href="<?= site_url('clients/'.$c['uid'].'/statement?from='.$billing['from'].'&to='.$billing['to']) ?>">PDF</a>
        <a class="btn ghost sm" target="_blank" href="<?= site_url('clients/'.$c['uid'].'/statement?invoices=1&from='.$billing['from'].'&to='.$billing['to']) ?>">PDF + open invoices</a>
      </form>
    </div>
    <div class="tbl-scroll">
    <table class="tbl">
      <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th style="text-align:right">Debit</th><th style="text-align:right">Credit</th><th style="text-align:right">Balance</th></tr></thead>
      <tbody>
        <tr style="background:#fafbfc"><td><?= esc($st['from']) ?></td><td></td><td><b>Opening balance</b></td><td></td><td></td><td style="text-align:right"><b><?= esc(money($st['opening'])) ?></b></td></tr>
        <?php foreach ($st['lines'] as $l): ?>
          <tr>
            <td><?= esc($l['date']) ?></td>
            <td class="mono" style="font-size:12px"><?= $l['uid'] ? '<a href="'.site_url('invoices/'.$l['uid']).'">'.esc($l['ref']).'</a>' : esc((string) $l['ref']) ?></td>
            <td><?= esc($l['description']) ?></td>
            <td style="text-align:right"><?= $l['debit'] > 0 ? esc(money($l['debit'])) : '' ?></td>
            <td style="text-align:right;color:var(--ok)"><?= $l['credit'] > 0 ? esc(money($l['credit'])) : '' ?></td>
            <td style="text-align:right"><?= esc(money($l['balance'])) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($st['lines'] === []): ?><tr><td colspan="6" class="sub" style="text-align:center;color:var(--ink-3)">No activity in this period.</td></tr><?php endif; ?>
        <tr style="background:#fafbfc"><td><?= esc($st['to']) ?></td><td></td><td><b>Closing balance</b></td><td></td><td></td><td style="text-align:right"><b><?= esc(money($st['closing'])) ?></b></td></tr>
      </tbody>
    </table>
    </div>
    <form method="post" action="<?= site_url('clients/'.$c['uid'].'/statement') ?>" class="card-b" style="border-top:1px solid var(--line);display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <?= csrf_field() ?>
      <input type="hidden" name="from" value="<?= esc($billing['from']) ?>">
      <input type="hidden" name="to" value="<?= esc($billing['to']) ?>">
      <span class="sub" style="font-size:13px">Email this statement + open invoices to</span>
      <input type="email" name="email" value="<?= esc(\App\Models\ClientModel::invoiceEmail($c)) ?>" required style="flex:1;min-width:220px;padding:8px 10px">
      <button class="btn sm">Send statement</button>
    </form>
  </div>

  <div x-show="tab==='invoices'" x-cloak class="card">
    <?php if (empty($billing['invoices'])): ?>
      <div class="empty" style="padding:22px 18px">No invoices yet.</div>
    <?php else: ?>
    <div class="tbl-scroll">
    <table class="tbl">
      <thead><tr><th>Invoice</th><th>Issued</th><th>Due</th><th>Status</th><th style="text-align:right">Total</th><th style="text-align:right">Outstanding</th></tr></thead>
      <tbody>
      <?php foreach ($billing['invoices'] as $i): ?>
        <tr>
          <td class="mono"><a href="<?= site_url('invoices/'.$i['uid']) ?>"><?= esc($i['invoice_no']) ?></a></td>
          <td><?= esc($i['issue_date']) ?></td>
          <td><?= esc($i['due_date']) ?></td>
          <td><?= invoice_badge($i) ?></td>
          <td style="text-align:right"><?= esc(money($i['total'])) ?></td>
          <td style="text-align:right;font-weight:600"><?= in_array($i['status'], ['void', 'draft'], true) ? '—' : esc(money(\App\Models\InvoiceModel::balance($i))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <div x-show="tab==='payments'" x-cloak class="card">
    <?php if (empty($billing['payments'])): ?>
      <div class="empty" style="padding:22px 18px">No payments recorded yet.</div>
    <?php else: ?>
    <div class="tbl-scroll">
    <table class="tbl">
      <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th>Applied to</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
      <?php foreach ($billing['payments'] as $p): ?>
        <tr>
          <td><?= esc($p['paid_at']) ?></td>
          <td><?= esc(\App\Models\PaymentModel::METHODS[$p['method']] ?? $p['method']) ?></td>
          <td class="mono" style="font-size:12px"><?= esc($p['reference'] ?: '—') ?></td>
          <td><?= $p['invoice_no'] ? '<a class="mono" href="'.site_url('invoices/'.$p['invoice_uid']).'">'.esc($p['invoice_no']).'</a>' : '<span class="sub">Credit on account</span>' ?></td>
          <td style="text-align:right;font-weight:600;color:var(--ok)"><?= esc(money($p['amount'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div x-show="tab==='inspections'" <?= $billing ? 'x-cloak' : '' ?> class="card">
    <?php if (empty($inspections)): ?>
      <div class="empty" style="padding:22px 18px">No inspections for this client yet.</div>
    <?php else: ?>
    <div class="tbl-scroll">
    <table class="tbl">
      <thead><tr><th>Date</th><th>Vehicle</th><th>Status</th><th>Score</th><?php if ($billing): ?><th style="text-align:right">Charge</th><?php endif; ?><th></th></tr></thead>
      <tbody>
      <?php foreach ($inspections as $i): ?>
        <tr>
          <td><?= esc($i['inspection_date']) ?></td>
          <td><b><?= esc(trim(($i['year'] ?: '').' '.($i['make'] ?: '').' '.($i['model'] ?: '')) ?: 'Vehicle') ?></b> <span class="mono sub" style="font-size:11.5px"><?= esc($i['plate'] ?: '') ?></span></td>
          <td><span class="pill <?= esc($i['status']) ?>"><?= esc(ucfirst($i['status'])) ?></span></td>
          <td><?= $i['score'] !== null ? esc($i['score']).'/100' : '—' ?></td>
          <?php if ($billing): ?><td style="text-align:right"><?= $i['charge_amount'] !== null ? esc(money($i['charge_amount'])) : '—' ?></td><?php endif; ?>
          <td style="text-align:right"><a class="btn ghost sm" href="<?= site_url(($i['status'] === 'completed' ? 'reports/' : 'inspections/').$i['report_uid'].($i['status'] === 'completed' ? '' : '/capture')) ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if (auth()->user()->inGroup('admin')): ?>
<form method="post" action="<?= site_url('clients/'.$c['uid'].'/delete') ?>" style="margin-top:22px" onsubmit="return confirm('Delete this client? This only works if they have no invoices.')">
  <?= csrf_field() ?><button class="btn danger sm">Delete client</button>
</form>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?= $this->endSection() ?>
