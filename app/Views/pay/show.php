<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= esc($title) ?> · <?= esc((string) billing('companyName')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  :root{--brand:<?= esc(brand('primary')) ?>;--ink:#1f2933;--ink-3:#7a8591;--line:#e2e6ea}
  *{box-sizing:border-box}
  body{margin:0;background:#f3f5f7;font-family:'Inter',system-ui,sans-serif;color:var(--ink);padding:24px 16px}
  .wrap{max-width:560px;margin:0 auto}
  .head{text-align:center;margin-bottom:18px}
  .head img{height:48px}
  .head .co{font-weight:700;margin-top:6px}
  .card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 18px 50px rgba(16,23,29,.08);overflow:hidden;margin-bottom:14px}
  .hero{padding:22px 24px;border-bottom:1px solid var(--line)}
  .lbl{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink-3)}
  .amt{font-size:38px;font-weight:800;letter-spacing:-.02em;margin:4px 0}
  .sub{color:var(--ink-3);font-size:13.5px}
  .rows{padding:6px 24px 14px}
  .row{display:flex;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid #f0f2f4;font-size:14px}
  .row:last-child{border-bottom:none}
  .row .k{color:var(--ink-3)}
  .row .v{font-weight:600;text-align:right}
  .mono{font-family:'JetBrains Mono',monospace}
  .opts{padding:18px 24px 22px;display:grid;gap:12px}
  .btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:15px;border-radius:12px;font-weight:700;font-size:16px;text-decoration:none;border:2px solid var(--brand)}
  .btn.primary{background:var(--brand);color:#fff}
  .btn.ghost{background:#fff;color:var(--brand)}
  .eft{border:1px solid var(--line);border-radius:12px;padding:14px 16px;background:#fafbfc}
  .eft h3{margin:0 0 6px;font-size:14px}
  .flash{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-weight:600;font-size:14px}
  .flash.ok{background:#e7f6ec;color:#15803d;border:1px solid #b7e2c4}
  .flash.err{background:#fbeae8;color:#b42318;border:1px solid #f1bdb7}
  .paid{display:inline-block;background:#e7f6ec;color:#15803d;border:1px solid #b7e2c4;border-radius:999px;padding:5px 12px;font-weight:700;font-size:13px}
  .foot{text-align:center;color:var(--ink-3);font-size:12px;margin-top:18px;line-height:1.6}
  .secure{display:flex;align-items:center;justify-content:center;gap:6px;color:var(--ink-3);font-size:12px}
</style>
</head>
<body>
<?php
  $eftOn   = setting('Billing.payEftEnabled') && trim((string) setting('Billing.bankAccountNumber')) !== '';
  $bank    = array_filter([
      'Bank'           => setting('Billing.bankName'),
      'Account name'   => setting('Billing.bankAccountName'),
      'Account number' => setting('Billing.bankAccountNumber'),
      'Branch code'    => setting('Billing.bankBranchCode'),
      'Account type'   => setting('Billing.bankAccountType'),
      'SWIFT / BIC'    => setting('Billing.bankSwift'),
  ], static fn ($v) => trim((string) $v) !== '');
  $settled = $amountDue <= 0;
?>
<div class="wrap">
  <div class="head">
    <img src="<?= esc(brand('logo')) ?>" alt="">
    <div class="co"><?= esc((string) billing('companyName')) ?></div>
  </div>

  <?php if (session()->getFlashdata('message')): ?><div class="flash ok"><?= esc(session()->getFlashdata('message')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?><div class="flash err"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

  <div class="card">
    <div class="hero">
      <div class="lbl"><?= $mode === 'invoice' ? 'Invoice ' . esc($invoice['invoice_no']) : 'Account statement' ?> · <?= esc($client['name'] ?? '') ?></div>
      <?php if ($settled): ?>
        <div class="amt"><?= esc(money(0)) ?></div>
        <span class="paid"><?= $amountDue < 0 ? 'In credit — ' . esc(money(abs($amountDue))) : 'Paid in full — thank you' ?></span>
      <?php else: ?>
        <div class="amt"><?= esc(money($amountDue)) ?></div>
        <div class="sub">
          <?php if ($mode === 'invoice'): ?>
            Due <?= esc(date('j F Y', strtotime($invoice['due_date']))) ?><?= \App\Models\InvoiceModel::isOverdue($invoice) ? ' — <b style="color:#b42318">overdue</b>' : '' ?>
          <?php else: ?>
            Total outstanding on your account
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="rows">
      <?php if ($mode === 'invoice'): ?>
        <?php foreach ($items as $it): ?>
          <div class="row"><span class="k"><?= esc($it['description']) ?></span><span class="v"><?= esc(money($it['line_total'])) ?></span></div>
        <?php endforeach; ?>
        <?php if ((float) $invoice['vat_amount'] > 0): ?><div class="row"><span class="k">VAT</span><span class="v"><?= esc(money($invoice['vat_amount'])) ?></span></div><?php endif; ?>
        <div class="row"><span class="k">Invoice total</span><span class="v"><?= esc(money($invoice['total'])) ?></span></div>
        <?php if ((float) $invoice['amount_paid'] > 0): ?><div class="row"><span class="k">Already paid</span><span class="v" style="color:#15803d">− <?= esc(money($invoice['amount_paid'])) ?></span></div><?php endif; ?>
      <?php else: ?>
        <?php foreach ($open as $o): ?>
          <div class="row"><span class="k"><span class="mono"><?= esc($o['invoice_no']) ?></span> · due <?= esc(date('j M Y', strtotime($o['due_date']))) ?></span><span class="v"><?= esc(money(\App\Models\InvoiceModel::balance($o))) ?></span></div>
        <?php endforeach; ?>
        <?php if ($open === []): ?><div class="row"><span class="k">No open invoices</span><span class="v"></span></div><?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if (! $settled): ?>
    <div class="opts">
      <?php if ($online): ?>
        <a class="btn primary" href="<?= esc($cardUrl) ?>">Pay <?= esc(money($amountDue)) ?> now</a>
        <div class="secure">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
          Card or instant EFT · secure checkout by Paystack
        </div>
      <?php endif; ?>
      <?php if ($eftOn): ?>
        <div class="eft">
          <h3>Pay by bank transfer (EFT)</h3>
          <?php foreach ($bank as $k => $v): ?>
            <div class="row"><span class="k"><?= esc($k) ?></span><span class="v mono"><?= esc((string) $v) ?></span></div>
          <?php endforeach; ?>
          <div class="row"><span class="k">Reference</span><span class="v mono"><?= esc($reference) ?></span></div>
          <?php if (trim((string) setting('Billing.paymentInstructions')) !== ''): ?>
            <div class="sub" style="margin-top:8px;font-size:12.5px"><?= esc((string) setting('Billing.paymentInstructions')) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if (! $online && ! $eftOn): ?>
        <div class="sub">Please contact us to arrange payment<?= setting('Billing.companyEmail') ? ': ' . esc((string) setting('Billing.companyEmail')) : '' ?>.</div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <a class="btn ghost" href="<?= esc($pdfUrl) ?>" target="_blank" style="background:#fff"><?= $mode === 'invoice' ? 'Download invoice (PDF)' : 'Download statement (PDF)' ?></a>

  <div class="foot">
    <?= esc((string) billing('companyName')) ?>
    <?= setting('Billing.companyPhone') ? ' · ' . esc((string) setting('Billing.companyPhone')) : '' ?>
    <?= setting('Billing.companyEmail') ? ' · ' . esc((string) setting('Billing.companyEmail')) : '' ?>
  </div>
</div>
</body>
</html>
