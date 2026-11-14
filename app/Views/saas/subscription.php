<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$money  = static fn ($v) => \Platform\Services\Billing::money($v);
$labels = \Platform\Models\TenantModel::STATUSES;
$open   = array_values(array_filter($invoices, static fn ($i) => $i['status'] === 'unpaid'));
$usage  = \Platform\Services\TenantData::usage($t);
$lim    = \App\Tenancy\Tenant::get()['limits'] ?? [];
?>
<div class="page-head" style="margin-bottom:18px"><h1 style="margin:0">Subscription</h1></div>

<?php foreach ($open as $inv): ?>
  <div class="flash <?= $inv['due_date'] < date('Y-m-d') ? 'err' : 'ok' ?>" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <span>Invoice <b><?= esc($inv['number']) ?></b> for <b><?= $money($inv['total']) ?></b> is due <?= date('d M Y', strtotime($inv['due_date'])) ?>.</span>
    <a class="btn sm" href="<?= esc(\Platform\Services\Billing::payUrl($inv), 'attr') ?>">Pay now</a>
  </div>
<?php endforeach; ?>

<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px">
  <div class="card" style="padding:20px">
    <h3 style="margin-top:0">Your plan</h3>
    <div style="font-size:22px;font-weight:800"><?= esc($pkg['name'] ?? '—') ?></div>
    <div class="muted"><?= $pkg ? $money($t['billing_cycle'] === 'annual' ? $pkg['price_annual'] : $pkg['price_monthly']) . ($t['billing_cycle'] === 'annual' ? ' per year' : ' per month') . ' excl. VAT' : '' ?></div>
    <p style="margin:14px 0 4px">Status: <b><?= esc($labels[$t['status']] ?? $t['status']) ?></b></p>
    <?php if ($t['status'] === 'trial' && $t['trial_ends_at']): ?><p style="margin:4px 0">Free trial until <b><?= date('d M Y', strtotime($t['trial_ends_at'])) ?></b></p><?php endif; ?>
    <?php if ($t['current_period_end']): ?><p style="margin:4px 0"><?= $t['cancel_at_period_end'] ? 'Ends on' : 'Renews on' ?> <b><?= date('d M Y', strtotime($t['current_period_end'])) ?></b></p><?php endif; ?>
    <?php if ($nextPkg): ?><p style="margin:4px 0">Changes to <b><?= esc($nextPkg['name']) ?></b> at renewal.</p><?php endif; ?>
    <p style="margin:4px 0">Card on file: <?= $t['card_label'] ? esc($t['card_label']) : '<span class="muted">none — saved when you next pay by card</span>' ?></p>
    <div style="margin-top:16px">
      <?php if ($t['cancel_at_period_end']): ?>
        <form method="post" action="<?= site_url('subscription/cancel') ?>"><?= csrf_field() ?><input type="hidden" name="undo" value="1"><button class="btn sm">Keep my subscription</button></form>
      <?php elseif (in_array($t['status'], ['active', 'trial', 'past_due'], true)): ?>
        <form method="post" action="<?= site_url('subscription/cancel') ?>" onsubmit="return confirm('<?= $t['status'] === 'trial' ? 'Cancel your trial now? Your workspace will close.' : 'Cancel your subscription? Everything keeps working until the end of the paid period.' ?>')"><?= csrf_field() ?><button class="btn ghost sm">Cancel subscription</button></form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="padding:20px">
    <h3 style="margin-top:0">Usage</h3>
    <?php foreach (['users' => $usage['users'], 'inspections_month' => $usage['inspections_month'], 'clients' => $usage['clients']] as $k => $used): $l = $lim[$k] ?? null; ?>
      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee"><span><?= esc($limitDef[$k]['name']) ?></span><b><?= number_format($used) ?> / <?= $l === null ? 'Unlimited' : number_format($l) ?></b></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card" style="padding:20px;margin-top:18px">
  <h3 style="margin-top:0">Change plan</h3>
  <p class="muted" style="margin-top:0"><?= $t['status'] === 'trial' ? 'During your trial you can switch freely.' : 'Upgrades apply straight away (you pay the difference for the rest of this period). Downgrades apply at your next renewal.' ?></p>
  <form method="post" action="<?= site_url('subscription/change') ?>">
    <?= csrf_field() ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
      <?php foreach ($packages as $p): $cur = (int) $p['id'] === (int) $t['package_id']; ?>
        <label style="display:block;border:2px solid <?= $cur ? '#37b34a' : '#e2e4e6' ?>;border-radius:12px;padding:14px;cursor:pointer">
          <input type="radio" name="package_id" value="<?= $p['id'] ?>" <?= $cur ? 'checked' : '' ?>>
          <b><?= esc($p['name']) ?></b><?= $cur ? ' <span class="muted">(current)</span>' : '' ?>
          <div style="font-size:18px;font-weight:800;margin-top:6px"><?= $money($p['price_monthly']) ?><span class="muted" style="font-size:12px;font-weight:400"> /month</span></div>
          <div class="muted" style="font-size:12.5px"><?= $money($p['price_annual']) ?> /year</div>
          <div class="muted" style="font-size:12.5px;margin-top:6px"><?= esc($p['tagline']) ?></div>
        </label>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:12px;align-items:center;margin-top:14px;flex-wrap:wrap">
      <select name="billing_cycle" style="width:auto"><option value="monthly">Pay monthly</option><option value="annual" <?= $t['billing_cycle'] === 'annual' ? 'selected' : '' ?>>Pay annually</option></select>
      <button class="btn">Change plan</button>
    </div>
  </form>
</div>

<div class="card" style="padding:20px;margin-top:18px">
  <h3 style="margin-top:0">Invoices</h3>
  <?php if (! $invoices): ?><p class="muted">No invoices yet.</p><?php else: ?>
  <div class="table-wrap" style="overflow-x:auto;-webkit-overflow-scrolling:touch"><table class="table" style="width:100%;min-width:560px">
    <thead><tr><th>Invoice</th><th>Description</th><th>Total</th><th>Due</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $inv): ?>
      <tr><td><?= esc($inv['number']) ?></td><td><?= esc($inv['description']) ?></td><td><?= $money($inv['total']) ?></td><td><?= date('d M Y', strtotime($inv['due_date'])) ?></td>
        <td><?= $inv['status'] === 'paid' ? 'Paid' : ($inv['status'] === 'void' ? 'Cancelled' : 'Unpaid') ?></td>
        <td><a href="<?= esc(\Platform\Services\Billing::payUrl($inv), 'attr') ?>"><?= $inv['status'] === 'unpaid' ? 'Pay' : 'View' ?></a></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
