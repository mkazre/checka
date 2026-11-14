<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Invoices<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $qs = http_build_query(array_filter(['status' => $status, 'q' => $q])); ?>
<div class="head">
  <div><h1>Invoices</h1><p class="sub">Subscription invoices to your customers. They're issued, chased and applied automatically.</p></div>
  <a class="btn sec" href="<?= site_url('invoices/export') . ($qs ? '?' . $qs : '') ?>">Export CSV</a>
</div>
<div class="grid g3" style="margin-bottom:18px">
  <div class="stat"><b><?= pf_money($collected) ?></b><span>Collected this month</span></div>
  <div class="stat"><b><?= pf_money($outstanding) ?></b><span>Outstanding</span></div>
  <div class="stat"><b style="color:var(--red)"><?= pf_money($overdue) ?></b><span>Overdue</span></div>
</div>
<form class="filters" method="get">
  <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Invoice number or company…">
  <select name="status" onchange="this.form.submit()">
    <?php foreach (['' => 'All', 'unpaid' => 'Unpaid', 'overdue' => 'Overdue', 'paid' => 'Paid', 'void' => 'Cancelled'] as $k => $l): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
  </select>
  <button class="btn sec">Search</button>
</form>
<?= view('Platform\Views\invoices\_table', ['rows' => $rows, 'showCompany' => true]) ?>
<?= $this->endSection() ?>
