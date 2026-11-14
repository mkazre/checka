<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Leads<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="head">
  <div><h1>Leads</h1><p class="sub">Enquiries from the website contact form and every website sign-up.</p></div>
  <a class="btn sec" href="<?= site_url('leads/export') ?>">Export CSV</a>
</div>
<div class="tabs">
  <?php foreach (['' => 'All', 'contact' => 'Contact form', 'signup' => 'Sign-ups'] as $k => $l): ?><a href="<?= site_url('leads') . ($k ? '?source=' . $k : '') ?>" class="<?= $src === $k ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?>
</div>
<div class="tbl"><table>
  <thead><tr><th>When</th><th>Who</th><th>Message</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr style="<?= $r['handled'] ? 'opacity:.6' : '' ?>">
      <td class="nowrap small"><?= pf_date($r['created_at'], true) ?><div><span class="badge"><?= $r['source'] === 'signup' ? 'Sign-up' : 'Contact' ?></span></div></td>
      <td><b><?= esc($r['name']) ?></b><?= $r['company'] ? ' · ' . esc($r['company']) : '' ?><div class="small"><a href="mailto:<?= esc($r['email'], 'attr') ?>"><?= esc($r['email']) ?></a> <?= esc($r['phone']) ?></div></td>
      <td class="small"><?php if ($r['topic']): ?><b><?= esc($r['topic']) ?></b><br><?php endif; ?><?= nl2br(esc($r['message'])) ?></td>
      <td class="right"><form method="post" action="<?= site_url('leads/' . $r['id'] . '/handled') ?>"><?= csrf_field() ?><button class="btn sm sec"><?= $r['handled'] ? 'Reopen' : 'Done' ?></button></form></td>
    </tr>
  <?php endforeach; ?>
  <?php if (! $rows): ?><tr><td colspan="4" class="empty">No leads yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?= $this->endSection() ?>
