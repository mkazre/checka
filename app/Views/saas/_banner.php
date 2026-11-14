<?php
/** Subscription notices at the top of every page for workspace admins (SaaS mode only). */
if (! \App\Tenancy\Tenant::active() || str_starts_with(uri_string(), 'subscription')) {
    return;
}
$t      = \App\Tenancy\Tenant::get();
$status = \App\Tenancy\Tenant::status();
$msg    = null;
if ($status === 'past_due') {
    $msg = ['err', 'Your subscription payment is overdue. Please pay now to avoid your workspace being paused.', 'Pay now'];
} elseif ($status === 'trial' && ! empty($t['trialEndsAt'])) {
    $days = (int) ceil((strtotime($t['trialEndsAt']) - time()) / 86400);
    if ($days <= 7) {
        $msg = ['info', 'Your free trial ends ' . ($days <= 0 ? 'today' : 'in ' . $days . ' day' . ($days === 1 ? '' : 's')) . '. Choose how to pay to keep everything running.', 'View subscription'];
    }
}
if (! $msg) {
    return;
}
?>
<div class="flash <?= $msg[0] === 'err' ? 'err' : 'ok' ?>" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
  <span><?= esc($msg[1]) ?></span><a class="btn sm" href="<?= site_url('subscription') ?>"><?= esc($msg[2]) ?></a>
</div>
