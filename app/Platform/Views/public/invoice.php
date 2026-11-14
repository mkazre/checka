<?php $money = static fn ($v) => \Platform\Services\Billing::money($v); $site = rtrim(str_replace('://app.', '://', \Platform\Services\Billing::appUrl()), '/'); ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Invoice <?= esc($inv['number']) ?></title>
<style>
body{font:14px/1.5 'Segoe UI',Roboto,Arial,sans-serif;color:#222;margin:0;background:#f4f5f6}
.page{max-width:780px;margin:24px auto;background:#fff;padding:44px;border:1px solid #e2e4e6}
.row{display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap}
h1{font-size:28px;margin:0;letter-spacing:.02em}
.muted{color:#6b7178}table{width:100%;border-collapse:collapse;margin-top:28px}th{text-align:left;font-size:12px;text-transform:uppercase;color:#6b7178;border-bottom:2px solid #222;padding:8px 0}
td{padding:10px 0;border-bottom:1px solid #eee}.r{text-align:right}.tot td{font-weight:800;font-size:16px;border-bottom:0}
.stamp{display:inline-block;border:2px solid;border-radius:6px;padding:3px 10px;font-weight:800;letter-spacing:.08em}
.print{max-width:780px;margin:0 auto;text-align:right;padding-top:16px}
@media print{body{background:#fff}.page{border:0;margin:0;padding:0}.print{display:none}}
</style><link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/icon-32.png"><link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
</head><body>
<div class="print"><button onclick="print()" style="padding:8px 16px;border-radius:8px;border:1px solid #ccc;background:#fff;cursor:pointer">Print / save as PDF</button></div>
<div class="page">
  <div class="row">
    <div><img src="<?= esc($site, 'attr') ?>/assets/logo.png" alt="" style="height:40px"><div style="margin-top:10px"><b><?= esc($general['companyName'] ?: $general['platformName']) ?></b><br>
      <span class="muted"><?= nl2br(esc($general['companyAddress'])) ?><?php if ($general['vatNumber']): ?><br>VAT no. <?= esc($general['vatNumber']) ?><?php endif; ?><?php if ($general['supportEmail']): ?><br><?= esc($general['supportEmail']) ?><?php endif; ?></span></div></div>
    <div style="text-align:right"><h1><?= (float) $inv['vat'] > 0 ? 'TAX INVOICE' : 'INVOICE' ?></h1>
      <div><?= esc($inv['number']) ?></div><div class="muted">Issued <?= date('d M Y', strtotime($inv['issue_date'])) ?> · Due <?= date('d M Y', strtotime($inv['due_date'])) ?></div>
      <div style="margin-top:10px"><?php if ($inv['status'] === 'paid'): ?><span class="stamp" style="color:#1f6b2c">PAID</span><?php elseif ($inv['status'] === 'void'): ?><span class="stamp" style="color:#888">VOID</span><?php endif; ?></div></div>
  </div>
  <div style="margin-top:28px"><div class="muted" style="font-size:12px;text-transform:uppercase">Bill to</div>
    <b><?= esc($t['company_name']) ?></b><br><?= esc($t['contact_name']) ?><br><?= esc($t['email']) ?><?php if ($t['vat_number']): ?><br>VAT no. <?= esc($t['vat_number']) ?><?php endif; ?></div>
  <table>
    <tr><th>Description</th><th class="r">Amount</th></tr>
    <tr><td><?= esc($inv['description']) ?><?php if ($inv['period_start'] && $inv['period_end']): ?><div class="muted"><?= date('d M Y', strtotime($inv['period_start'])) ?> – <?= date('d M Y', strtotime($inv['period_end'])) ?></div><?php endif; ?></td><td class="r"><?= $money($inv['subtotal']) ?></td></tr>
    <tr><td class="r muted">VAT <?= rtrim(rtrim((string) $inv['vat_rate'], '0'), '.') ?>%</td><td class="r"><?= $money($inv['vat']) ?></td></tr>
    <tr class="tot"><td class="r">Total</td><td class="r"><?= $money($inv['total']) ?></td></tr>
  </table>
  <?php if ($inv['status'] === 'unpaid'): ?>
    <p style="margin-top:28px">Pay online: <a href="<?= site_url('pay/' . $inv['uid']) ?>"><?= site_url('pay/' . $inv['uid']) ?></a></p>
    <?php if (! empty($eft['enabled'])): ?><p class="muted">Bank transfer: <?= esc($eft['bankName']) ?> · <?= esc($eft['accountName']) ?> · Acc <?= esc($eft['accountNumber']) ?> · Branch <?= esc($eft['branchCode']) ?> · Ref <b><?= esc($inv['number']) ?></b></p><?php endif; ?>
  <?php endif; ?>
</div></body></html>
