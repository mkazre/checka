<?php /** @var list<array> $rows @var bool $showCompany @var array|null $tenant */ ?>
<div class="tbl"><table>
  <thead><tr><th>Invoice</th><?php if ($showCompany): ?><th>Company</th><?php endif; ?><th>Description</th><th class="right">Total</th><th>Due</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $over = $r['status'] === 'unpaid' && $r['due_date'] < date('Y-m-d'); ?>
    <tr>
      <td class="nowrap"><a href="<?= esc(\Platform\Services\Billing::payUrl($r), 'attr') ?>" target="_blank" rel="noopener"><?= esc($r['number']) ?></a><div class="muted small"><?= esc(ucfirst($r['kind'])) ?></div></td>
      <?php if ($showCompany): ?><td><a href="<?= site_url('tenants/' . $r['tenant_id']) ?>"><?= esc($r['company_name'] ?? '#' . $r['tenant_id']) ?></a></td><?php endif; ?>
      <td class="small"><?= esc($r['description']) ?></td>
      <td class="right nowrap"><b><?= pf_money($r['total']) ?></b></td>
      <td class="nowrap small"><?= pf_date($r['due_date']) ?></td>
      <td><?php if ($r['status'] === 'paid'): ?><span class="badge s-active">Paid</span><div class="muted small"><?= pf_date($r['paid_at']) ?></div>
        <?php elseif ($r['status'] === 'void'): ?><span class="badge s-cancelled">Cancelled</span>
        <?php else: ?><span class="badge <?= $over ? 's-suspended' : 's-past_due' ?>"><?= $over ? 'Overdue' : 'Unpaid' ?></span><?php endif; ?></td>
      <td class="right">
        <?php if ($r['status'] === 'unpaid'): ?>
          <details style="display:inline-block;text-align:left"><summary class="btn sm sec" style="list-style:none">Actions</summary>
            <div class="card" style="position:absolute;right:30px;z-index:5;width:290px;margin-top:6px;box-shadow:0 12px 30px rgba(0,0,0,.12)">
              <form method="post" action="<?= site_url('invoices/' . $r['id'] . '/paid') ?>" data-confirm="Mark <?= esc($r['number'], 'attr') ?> as paid? This activates/extends the subscription and emails a receipt.">
                <?= csrf_field() ?><label>Mark as paid</label>
                <select name="method" style="margin-bottom:6px"><option value="eft">Bank transfer (EFT)</option><option value="cash">Cash</option><option value="manual">Other</option></select>
                <input type="text" name="reference" placeholder="Bank reference (optional)" style="margin-bottom:8px"><button class="btn sm">Mark paid</button></form>
              <div class="row-actions" style="margin-top:12px">
                <form method="post" action="<?= site_url('invoices/' . $r['id'] . '/resend') ?>"><?= csrf_field() ?><button class="btn sm sec">Email again</button></form>
                <?php if (! empty($tenant['paystack_auth'])): ?><form method="post" action="<?= site_url('invoices/' . $r['id'] . '/charge') ?>" data-confirm="Charge the saved card now?"><?= csrf_field() ?><button class="btn sm sec">Charge card</button></form><?php endif; ?>
                <form method="post" action="<?= site_url('invoices/' . $r['id'] . '/void') ?>" data-confirm="Cancel this invoice?"><?= csrf_field() ?><button class="btn sm sec">Cancel</button></form>
              </div>
            </div></details>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (! $rows): ?><tr><td colspan="7" class="empty">No invoices.</td></tr><?php endif; ?>
  </tbody>
</table></div>
