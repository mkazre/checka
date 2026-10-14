<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $isAdmin = auth()->user()->inGroup('admin'); ?>

<div class="page-head">
  <div><h1>Invoices</h1><div class="sub">Bill clients for inspections, record payments, send statements.</div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('invoices/payments') ?>">Payments</a>
  <?php if ($isAdmin): ?><a class="btn ghost" href="<?= site_url('invoices/settings') ?>">Billing Settings</a><?php endif; ?>
  <a class="btn" href="<?= site_url('invoices/new') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> New Invoice
  </a>
</div>

<div class="grid cols-4" style="margin-bottom:16px">
  <a class="kpi" href="<?= site_url('invoices?status=sent') ?>" style="text-decoration:none;color:inherit"><div class="k">Outstanding</div><div class="v"><?= esc(money($kpi['outstanding'])) ?></div></a>
  <a class="kpi" href="<?= site_url('invoices?status=overdue') ?>" style="text-decoration:none;color:inherit"><div class="k">Overdue</div><div class="v" style="color:<?= $kpi['overdue'] > 0 ? 'var(--major)' : 'inherit' ?>"><?= esc(money($kpi['overdue'])) ?></div></a>
  <a class="kpi" href="<?= site_url('invoices/payments') ?>" style="text-decoration:none;color:inherit"><div class="k">Received this month</div><div class="v" style="color:var(--ok)"><?= esc(money($kpi['paidThisMonth'])) ?></div></a>
  <a class="kpi" href="<?= site_url('invoices?status=draft') ?>" style="text-decoration:none;color:inherit"><div class="k">Drafts to issue</div><div class="v"><?= (int) $kpi['drafts'] ?></div></a>
</div>

<?php if ($isAdmin && trim((string) setting('Billing.bankAccountNumber')) === '' && trim((string) setting('Pay.paystackSecret')) === ''): ?>
  <div class="flash err" style="margin-bottom:14px">No payment options are set up yet, so invoices can't tell clients how to pay. Add your bank details in <a href="<?= site_url('invoices/settings') ?>"><b>Billing Settings</b></a>.</div>
<?php endif; ?>

<div class="card">
  <div class="card-h" style="flex-wrap:wrap;gap:8px">
    <div style="display:flex;gap:4px;flex-wrap:wrap">
      <?php foreach (['' => 'All', 'draft' => 'Draft', 'sent' => 'Due', 'overdue' => 'Overdue', 'partial' => 'Part paid', 'paid' => 'Paid', 'void' => 'Void'] as $k => $l): ?>
        <a class="btn sm <?= $status === $k ? 'dark' : 'ghost' ?>" href="<?= site_url('invoices'.($k !== '' ? '?status='.$k : '')) ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    <form method="get" action="<?= site_url('invoices') ?>" style="margin-left:auto">
      <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= esc($status) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Invoice no or client…" style="min-width:220px;padding:7px 10px;font-size:13px">
    </form>
  </div>
  <?php if (empty($invoices)): ?>
    <div class="empty" style="padding:28px 18px">No invoices here yet. Complete an inspection and use <b>Generate invoice</b> on its report, or create one with <b>New Invoice</b>.</div>
  <?php else: ?>
  <div class="tbl-scroll">
  <table class="tbl">
    <thead><tr><th>Invoice</th><th>Client</th><th>Issued</th><th>Due</th><th>Status</th><th style="text-align:right">Total</th><th style="text-align:right">Outstanding</th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $i): ?>
      <tr onclick="location.href='<?= site_url('invoices/'.$i['uid']) ?>'" style="cursor:pointer">
        <td class="mono"><a href="<?= site_url('invoices/'.$i['uid']) ?>"><b><?= esc($i['invoice_no']) ?></b></a><?= $i['inspection_id'] ? ' <span class="pill" style="font-size:9.5px;padding:1px 6px" title="Linked to an inspection">INSP</span>' : '' ?></td>
        <td><?= esc($i['client_name'] ?? '—') ?></td>
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

<?= $this->endSection() ?>
