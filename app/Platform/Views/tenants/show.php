<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?><?= esc($t['company_name']) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $url = pf_workspace_url($t['slug']); $ready = (bool) $t['provisioned_at']; ?>
<div class="head">
  <div>
    <h1><?= esc($t['company_name']) ?> <?= pf_status($t['status']) ?></h1>
    <p class="sub"><a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener"><?= esc($t['slug'] . '.' . pf_root()) ?></a> · <?= esc($pkg['name'] ?? 'No package') ?> (<?= $t['billing_cycle'] === 'annual' ? 'annual' : 'monthly' ?>)</p>
  </div>
  <div class="row-actions">
    <?php if ($ready): ?>
      <form method="post" action="<?= site_url('tenants/' . $t['id'] . '/login-as') ?>" target="_blank"><?= csrf_field() ?><button class="btn">Log in as admin ↗</button></form>
    <?php endif; ?>
    <a class="btn sec" href="<?= site_url('tenants/' . $t['id'] . '/edit') ?>">Edit / overrides</a>
  </div>
</div>

<?php if ($t['provision_error']): ?>
  <div class="flash err"><b>Setup failed:</b> <?= esc($t['provision_error']) ?>
    <form method="post" action="<?= site_url('tenants/' . $t['id'] . '/action') ?>" style="margin-top:8px"><?= csrf_field() ?><input type="hidden" name="do" value="retry"><button class="btn sm">Retry setup</button></form></div>
<?php elseif ($t['status'] === 'provisioning'): ?>
  <div class="flash info">Waiting for the setup job — it runs every minute. If this stays here, check the provisioning cron job under <a href="<?= site_url('settings/general') ?>">Settings</a>.</div>
<?php endif; ?>

<div class="grid g2">
  <div>
    <div class="card">
      <h2>Usage this month</h2>
      <?php
      $rows = ['users' => $usage['users'], 'inspections_month' => $usage['inspections_month'], 'clients' => $usage['clients'], 'storage_mb' => $usage['storage_mb']];
      foreach ($rows as $k => $used): $lim = $eff['limits'][$k] ?? null; $pct = $lim ? min(100, round($used / max(1, $lim) * 100)) : 0; ?>
        <div style="margin-bottom:12px">
          <div style="display:flex;justify-content:space-between"><span><?= esc($limitDef[$k]['name']) ?></span><span><b><?= is_float($used) ? number_format($used, 1) : number_format($used) ?></b> <span class="muted">/ <?= $lim === null ? 'Unlimited' : number_format($lim) ?></span></span></div>
          <?php if ($lim !== null): ?><div class="bar"><i class="<?= $pct >= 90 ? 'hi' : '' ?>" style="width:<?= $pct ?>%"></i></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
      <div class="muted small"><?= number_format($usage['inspections_total']) ?> inspections in total.
        <?php if ($usage['clients']): ?><a href="<?= site_url('tenants/' . $t['id'] . '/clients.csv') ?>">Export their clients (CSV)</a><?php endif; ?></div>
    </div>

    <div class="card">
      <h2>Subscription</h2>
      <dl class="kv">
        <dt>Status</dt><dd><?= pf_status($t['status']) ?></dd>
        <dt>Package</dt><dd><?= esc($pkg['name'] ?? '—') ?><?= $pkg ? ' · ' . pf_money($t['billing_cycle'] === 'annual' ? $pkg['price_annual'] : $pkg['price_monthly']) . ($t['billing_cycle'] === 'annual' ? '/year' : '/month') : '' ?></dd>
        <dt>Trial ends</dt><dd><?= pf_date($t['trial_ends_at']) ?></dd>
        <dt>Paid until</dt><dd><?= pf_date($t['current_period_end']) ?></dd>
        <?php if ($t['suspended_at']): ?><dt>Paused since</dt><dd><?= pf_date($t['suspended_at'], true) ?></dd><?php endif; ?>
        <?php if ($t['cancelled_at']): ?><dt>Cancelled</dt><dd><?= pf_date($t['cancelled_at'], true) ?></dd><?php endif; ?>
        <?php if ($t['cancel_at_period_end']): ?><dt>Ends</dt><dd><span class="badge s-past_due">Cancels on <?= pf_date($t['current_period_end']) ?></span></dd><?php endif; ?>
        <?php if ($nextPkg): ?><dt>Next renewal</dt><dd>Changes to <?= esc($nextPkg['name']) ?> (<?= esc($t['next_cycle'] ?: $t['billing_cycle']) ?>)</dd><?php endif; ?>
        <dt>Card on file</dt><dd><?= $t['card_label'] ? esc($t['card_label']) . ' <span class="muted small">(charged automatically on renewal)</span>' : '—' ?></dd>
      </dl>
      <div class="row-actions" style="margin-top:16px">
        <?php $act = static fn (string $do, string $label, string $cls, string $confirm = '') => '<form method="post" action="' . site_url('tenants/' . $t['id'] . '/action') . '"' . ($confirm ? ' data-confirm="' . esc($confirm, 'attr') . '"' : '') . '>' . csrf_field() . '<input type="hidden" name="do" value="' . $do . '"><button class="btn sm ' . $cls . '">' . $label . '</button></form>'; ?>
        <?php if (in_array($t['status'], ['active', 'trial', 'past_due'], true)): ?>
          <?= $act('suspend', 'Pause workspace', 'amb', 'Pause this workspace? Its users will see a "paused" page until you resume it.') ?>
        <?php elseif ($t['status'] === 'suspended'): ?>
          <?= $act('resume', 'Resume workspace', '') ?>
        <?php elseif ($t['status'] === 'cancelled'): ?>
          <?= $act('reopen', 'Reopen workspace', '') ?>
        <?php endif; ?>
        <?php if ($t['status'] !== 'cancelled' && $t['status'] !== 'provisioning'): ?>
          <?= $act('cancel', 'Cancel subscription', 'red', 'Cancel this workspace? It closes now; data is kept for the retention period.') ?>
        <?php endif; ?>
      </div>
      <form method="post" action="<?= site_url('tenants/' . $t['id'] . '/action') ?>" style="display:flex;gap:8px;align-items:center;margin-top:12px">
        <?= csrf_field() ?><input type="hidden" name="do" value="extend_trial">
        <span class="small">Extend trial by</span><input type="number" name="days" value="7" min="1" max="365" style="width:80px"><span class="small">days</span><button class="btn sm sec">Extend</button>
      </form>
    </div>
  </div>

  <div>
    <div class="card">
      <h2>Company</h2>
      <dl class="kv">
        <dt>Contact</dt><dd><?= esc($t['contact_name'] ?: '—') ?></dd>
        <dt>Email</dt><dd><a href="mailto:<?= esc($t['email'], 'attr') ?>"><?= esc($t['email']) ?></a></dd>
        <dt>Phone</dt><dd><?= esc($t['phone'] ?: '—') ?></dd>
        <dt>VAT no.</dt><dd><?= esc($t['vat_number'] ?: '—') ?></dd>
        <dt>Created</dt><dd><?= pf_date($t['created_at'], true) ?></dd>
        <dt>Set up</dt><dd><?= pf_date($t['provisioned_at'], true) ?></dd>
        <dt>Data</dt><dd class="small">Tables prefixed <code><?= esc($t['db_prefix'] === '' ? '(none)' : $t['db_prefix']) ?></code> · storage <?= number_format($usage['storage_mb'], 1) ?> MB</dd>
        <?php if ($t['notes']): ?><dt>Notes</dt><dd><?= nl2br(esc($t['notes'])) ?></dd><?php endif; ?>
      </dl>
    </div>
    <div class="card">
      <h2>Modules <small><?= count($eff['modules']) ?> switched on</small></h2>
      <?php $extra = (array) json_decode((string) ($t['extra_modules'] ?? '[]'), true);
      foreach ($eff['modules'] as $k): if (! isset($catalog[$k])) continue; ?>
        <span class="tag <?= $catalog[$k]['status'] === 'roadmap' ? 'road' : '' ?>" title="<?= in_array($k, $extra, true) ? 'Added for this workspace' : 'From package' ?>"><?= esc($catalog[$k]['name']) ?><?= in_array($k, $extra, true) ? ' +' : '' ?></span>
      <?php endforeach; ?>
      <p class="muted small" style="margin:8px 0 0">“+” = added just for this workspace. Amber = coming soon.</p>
    </div>
    <div class="card">
      <h2>History</h2>
      <?php if (! $events): ?><p class="muted">Nothing yet.</p><?php endif; ?>
      <?php foreach ($events as $e): ?>
        <div class="ev"><time><?= pf_date($e['created_at'], true) ?></time><div><?= esc($e['message']) ?> <span class="muted small">— <?= esc($e['actor'] ?: 'system') ?></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
    <h2 style="margin:0">Invoices</h2>
    <details><summary class="btn sm sec" style="list-style:none">+ Manual invoice</summary>
      <form method="post" action="<?= site_url('tenants/' . $t['id'] . '/invoice') ?>" class="card" style="margin-top:8px;max-width:420px">
        <?= csrf_field() ?>
        <div class="f"><label>Description</label><input type="text" name="description" required placeholder="e.g. Extra user seats — March"></div>
        <div class="grid g2">
          <div class="f"><label>Amount excl. VAT</label><input type="number" step="0.01" min="0.01" name="amount" required></div>
          <div class="f"><label>Due in (days)</label><input type="number" min="0" name="due_days" value="7"></div>
        </div>
        <label class="check"><input type="checkbox" name="send" value="1" checked><span>Email it to <?= esc($t['email']) ?></span></label>
        <button class="btn sm">Create invoice</button>
      </form></details>
  </div>
  <?= view('Platform\Views\invoices\_table', ['rows' => $invoices, 'showCompany' => false, 'tenant' => $t]) ?>
</div>
<?= $this->endSection() ?>
