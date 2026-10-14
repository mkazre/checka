<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<style>
  .inv-wrap{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start}
  @media(max-width:980px){.inv-wrap{grid-template-columns:1fr}}
  .paper{background:#fff;border:1px solid var(--line);border-radius:12px;padding:28px 30px;box-shadow:0 12px 40px rgba(16,23,29,.06)}
  .paper .top{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;border-bottom:3px solid var(--brand);padding-bottom:16px;margin-bottom:18px}
  .paper .title{font-family:var(--display);font-size:26px;font-weight:800;letter-spacing:.04em;color:var(--brand);text-align:right}
  .paper .meta{display:grid;grid-template-columns:auto auto;gap:2px 14px;font-size:13px;justify-content:end;margin-top:6px}
  .paper .meta .k{color:var(--ink-3)} .paper .meta .v{font-weight:600;text-align:right}
  .lbl{font-family:var(--mono);font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3);font-weight:700;margin-bottom:4px}
  .items{width:100%;border-collapse:collapse;margin-top:18px}
  .items th{background:var(--ink);color:#fff;font-family:var(--mono);font-size:10px;letter-spacing:.1em;text-transform:uppercase;padding:9px 10px;text-align:left}
  .items td{padding:10px;border-bottom:1px solid var(--line);vertical-align:top}
  .items .r{text-align:right}
  .tot{margin-left:auto;min-width:280px;margin-top:12px;font-size:14px}
  .tot div{display:flex;justify-content:space-between;padding:4px 0}
  .due{background:var(--brand);color:#fff;border-radius:9px;padding:12px 14px;margin-top:8px;display:flex;justify-content:space-between;align-items:center}
  .due b{font-size:22px}
  .side .card{margin-bottom:14px}
  .side form{display:grid;gap:10px}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
  $inv = $b['invoice']; $c = $b['client'] ?? []; $items = $b['items'];
  $vat = (float) $inv['vat_rate'] > 0;
  $editable = $inv['status'] !== 'void';
  $isAdmin  = auth()->user()->inGroup('admin');
  $email    = \App\Models\ClientModel::invoiceEmail($c);
?>

<div class="page-head">
  <div><h1><?= esc($inv['invoice_no']) ?> <?= invoice_badge($inv) ?></h1><div class="sub"><?= esc($c['name'] ?? '') ?> · issued <?= esc($inv['issue_date']) ?><?= $inv['sent_at'] ? ' · emailed ' . esc(substr($inv['sent_at'], 0, 16)) : '' ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('invoices') ?>">All invoices</a>
  <?php if ($editable): ?><a class="btn ghost" href="<?= site_url('invoices/'.$inv['uid'].'/edit') ?>">Edit</a><?php endif; ?>
  <a class="btn dark" target="_blank" href="<?= site_url('invoices/'.$inv['uid'].'/pdf') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg> PDF
  </a>
</div>

<div class="inv-wrap">
  <div class="paper">
    <div class="top">
      <div>
        <img src="<?= esc(brand('logo')) ?>" alt="" style="height:40px"><br>
        <b style="font-size:15px"><?= esc((string) billing('companyName')) ?></b>
        <div class="sub" style="font-size:12px;color:var(--ink-3);line-height:1.5"><?= nl2br(esc((string) setting('Billing.companyAddress'))) ?><?= setting('Billing.companyVatNo') ? '<br>VAT ' . esc((string) setting('Billing.companyVatNo')) : '' ?></div>
      </div>
      <div>
        <div class="title"><?= $vat ? 'TAX INVOICE' : 'INVOICE' ?></div>
        <div class="meta">
          <span class="k">Invoice no</span><span class="v mono"><?= esc($inv['invoice_no']) ?></span>
          <span class="k">Issue date</span><span class="v"><?= esc(date('j M Y', strtotime($inv['issue_date']))) ?></span>
          <span class="k">Due date</span><span class="v"><?= esc(date('j M Y', strtotime($inv['due_date']))) ?></span>
        </div>
      </div>
    </div>

    <div class="grid cols-2">
      <div>
        <div class="lbl">Bill to</div>
        <b><a href="<?= site_url('clients/'.($c['uid'] ?? '')) ?>"><?= esc($c['name'] ?? '—') ?></a></b>
        <div style="font-size:13px;color:var(--ink-2);line-height:1.5">
          <?= ! empty($c['contact_person']) ? 'Attn: ' . esc($c['contact_person']) . '<br>' : '' ?>
          <?= ! empty($c['address']) ? nl2br(esc($c['address'])) . '<br>' : '' ?>
          <?= ! empty($c['vat_number']) ? 'VAT ' . esc($c['vat_number']) . '<br>' : '' ?>
          <span style="color:var(--ink-3)"><?= esc(trim(implode(' · ', array_filter([$c['email'] ?? '', $c['phone'] ?? ''])))) ?></span>
        </div>
      </div>
      <?php if ($b['inspection']): $ins = $b['inspection']; $v = $b['vehicle']; ?>
      <div>
        <div class="lbl">Inspection</div>
        <b><?= esc($v ? model(\App\Models\VehicleModel::class)->label($v) : 'Vehicle') ?></b>
        <div style="font-size:13px;color:var(--ink-2);line-height:1.5">
          <?= ! empty($v['plate']) ? esc($v['plate']) . '<br>' : '' ?>
          Report <a class="mono" href="<?= site_url('reports/'.$ins['report_uid']) ?>"><?= esc(strtoupper(substr($ins['report_uid'], 0, 8))) ?></a> · <?= esc($ins['inspection_date']) ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <table class="items">
      <thead><tr><th>Description</th><th class="r">Qty</th><th class="r">Unit price</th><th class="r">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= esc($it['description']) ?></td>
            <td class="r"><?= esc(rtrim(rtrim(number_format((float) $it['qty'], 2, '.', ''), '0'), '.')) ?></td>
            <td class="r"><?= esc(money($it['unit_price'])) ?></td>
            <td class="r"><?= esc(money($it['line_total'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="tot">
      <div><span style="color:var(--ink-3)">Subtotal<?= $vat ? ' (excl. VAT)' : '' ?></span><span><?= esc(money($inv['subtotal'])) ?></span></div>
      <?php if ($vat): ?><div><span style="color:var(--ink-3)">VAT @ <?= esc(rtrim(rtrim(number_format((float) $inv['vat_rate'], 2), '0'), '.')) ?>%</span><span><?= esc(money($inv['vat_amount'])) ?></span></div><?php endif; ?>
      <div style="border-top:1px solid var(--line);font-weight:700"><span>Total</span><span><?= esc(money($inv['total'])) ?></span></div>
      <?php if ((float) $inv['amount_paid'] > 0): ?><div style="color:var(--ok)"><span>Paid</span><span>− <?= esc(money($inv['amount_paid'])) ?></span></div><?php endif; ?>
      <div class="due"><span>Amount due</span><b><?= esc(money($b['balance'])) ?></b></div>
    </div>

    <?php if (trim((string) $inv['notes']) !== ''): ?>
      <div style="margin-top:18px"><div class="lbl">Notes</div><div style="font-size:13px;color:var(--ink-2)"><?= nl2br(esc($inv['notes'])) ?></div></div>
    <?php endif; ?>

    <?php if (! empty($b['payments'])): ?>
      <div style="margin-top:18px">
        <div class="lbl">Payments</div>
        <table class="tbl" style="font-size:13px">
          <?php foreach ($b['payments'] as $p): ?>
            <tr>
              <td><?= esc($p['paid_at']) ?></td>
              <td><?= esc(\App\Models\PaymentModel::METHODS[$p['method']] ?? $p['method']) ?></td>
              <td class="mono" style="font-size:12px"><?= esc($p['reference'] ?: '') ?></td>
              <td style="text-align:right;font-weight:600;color:var(--ok)"><?= esc(money($p['amount'])) ?></td>
              <?php if ($isAdmin): ?>
              <td style="text-align:right">
                <form method="post" action="<?= site_url('payments/'.$p['id'].'/delete') ?>" onsubmit="return confirm('Remove this payment? The invoice balance goes back up.')"><?= csrf_field() ?><button class="btn ghost sm">Remove</button></form>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="side">
    <?php if ($inv['status'] === 'draft'): ?>
    <div class="card">
      <div class="card-b">
        <p style="margin:0 0 10px;font-size:13px;color:var(--ink-2)">This is a <b>draft</b> — it isn't on the client's statement yet. Emailing it issues it automatically.</p>
        <form method="post" action="<?= site_url('invoices/'.$inv['uid'].'/issue') ?>"><?= csrf_field() ?><button class="btn ghost sm">Mark as issued (without emailing)</button></form>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($inv['status'] !== 'void'): ?>
    <div class="card">
      <div class="card-h"><h3>Email</h3></div>
      <div class="card-b">
        <form method="post" action="<?= site_url('invoices/'.$inv['uid'].'/send') ?>">
          <?= csrf_field() ?>
          <label class="fld"><span>To</span><input type="email" name="email" value="<?= esc($email) ?>" required></label>
          <label class="fld"><span>CC (optional)</span><input type="email" name="cc"></label>
          <?php if ($b['inspection'] && $b['inspection']['status'] === 'completed'): ?>
            <label style="display:flex;gap:8px;align-items:center;font-size:13px"><input type="checkbox" name="with_report" value="1" checked> Include the inspection report (PDF + link)</label>
          <?php endif; ?>
          <button class="btn">Send invoice</button>
          <div class="sub" style="font-size:11.5px;color:var(--ink-3)">Includes the PDF, a Pay Now button and your bank details.</div>
        </form>
      </div>
    </div>

    <?php if ($b['balance'] > 0): ?>
    <div class="card">
      <div class="card-h"><h3>Record payment</h3></div>
      <div class="card-b">
        <form method="post" action="<?= site_url('invoices/payments') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="client_id" value="<?= (int) $inv['client_id'] ?>">
          <input type="hidden" name="invoice_id" value="<?= (int) $inv['id'] ?>">
          <input type="hidden" name="back" value="invoices/<?= esc($inv['uid']) ?>">
          <div class="grid cols-2" style="gap:10px">
            <label class="fld"><span>Amount</span><input type="number" name="amount" step="0.01" min="0.01" value="<?= esc(number_format($b['balance'], 2, '.', '')) ?>" required></label>
            <label class="fld"><span>Date</span><input type="date" name="paid_at" value="<?= date('Y-m-d') ?>"></label>
          </div>
          <label class="fld"><span>Method</span>
            <select name="method"><?php foreach (\App\Models\PaymentModel::METHODS as $k => $l): ?><option value="<?= $k ?>"><?= esc($l) ?></option><?php endforeach; ?></select>
          </label>
          <label class="fld"><span>Reference</span><input type="text" name="reference" placeholder="Bank ref / receipt no"></label>
          <button class="btn">Record payment</button>
          <div class="sub" style="font-size:11.5px;color:var(--ink-3)">Overpayments go to the client's other open invoices, then credit.</div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-h"><h3>Client pay link</h3></div>
      <div class="card-b">
        <?php if ($inv['status'] === 'draft'): ?>
          <div class="sub" style="font-size:12.5px;color:var(--ink-3)">Available once the invoice is issued.</div>
        <?php else: ?>
          <input type="text" readonly value="<?= esc($b['payUrl']) ?>" onclick="this.select()" class="mono" style="font-size:11.5px">
          <a class="btn ghost sm" style="margin-top:8px" target="_blank" href="<?= esc($b['payUrl']) ?>">Open pay page ↗</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php if ($inv['status'] !== 'void' && (float) $inv['amount_paid'] == 0.0 && $inv['status'] !== 'draft'): ?>
        <form method="post" action="<?= site_url('invoices/'.$inv['uid'].'/void') ?>" onsubmit="return confirm('Void this invoice? It will be removed from the client\'s balance. This can\'t be undone.')"><?= csrf_field() ?><button class="btn ghost sm">Void invoice</button></form>
      <?php endif; ?>
      <?php if ($inv['status'] === 'draft' && $isAdmin): ?>
        <form method="post" action="<?= site_url('invoices/'.$inv['uid'].'/delete') ?>" onsubmit="return confirm('Delete this draft?')"><?= csrf_field() ?><button class="btn danger sm">Delete draft</button></form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
