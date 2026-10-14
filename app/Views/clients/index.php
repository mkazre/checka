<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Clients</h1><div class="sub">Everyone you inspect for and bill. Every inspection must be linked to a client.</div></div>
  <div class="spacer"></div>
  <a class="btn" href="<?= site_url('clients/new') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> New Client
  </a>
</div>

<div class="card">
  <div class="card-h">
    <h3>Register <span class="sub" style="font-weight:400;color:var(--ink-3)">(<?= count($clients) ?>)</span></h3>
    <form method="get" action="<?= site_url('clients') ?>" style="margin-left:auto;display:flex;gap:6px">
      <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Search name, contact, email, phone, VAT…" style="min-width:240px;padding:7px 10px;font-size:13px">
      <?php if ($q !== ''): ?><a class="btn ghost sm" href="<?= site_url('clients') ?>">Clear</a><?php endif; ?>
    </form>
  </div>
  <?php if (empty($clients)): ?>
    <div class="empty" style="padding:28px 18px">
      <?= $q !== '' ? 'No clients match your search.' : 'No clients yet — add your first client to start inspecting and billing.' ?>
    </div>
  <?php else: ?>
  <div class="tbl-scroll">
  <table class="tbl">
    <thead><tr><th>Client</th><th>Contact</th><th>Phone / Email</th><?php if (feature('billing')): ?><th style="text-align:right">Balance</th><?php endif; ?><th></th></tr></thead>
    <tbody>
    <?php foreach ($clients as $c): ?>
      <tr>
        <td>
          <a href="<?= site_url('clients/'.$c['uid']) ?>"><b><?= esc($c['name']) ?></b></a>
          <div class="sub" style="font-size:11.5px;color:var(--ink-3)"><?= $c['type'] === 'individual' ? 'Individual' : 'Business' ?><?= $c['vat_number'] ? ' · VAT '.esc($c['vat_number']) : '' ?></div>
        </td>
        <td><?= esc($c['contact_person'] ?: '—') ?></td>
        <td style="font-size:13px"><?= esc($c['phone'] ?: '') ?><div class="sub" style="color:var(--ink-3)"><?= esc($c['email'] ?: '') ?></div></td>
        <?php if (feature('billing')): $bal = $balances[$c['id']] ?? 0; ?>
          <td style="text-align:right;font-weight:600;color:<?= $bal > 0 ? 'var(--major)' : 'var(--ink-3)' ?>"><?= $bal < 0 ? 'Cr ' : '' ?><?= esc(money(abs($bal))) ?></td>
        <?php endif; ?>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="<?= site_url('inspections/new?client='.$c['uid']) ?>">New inspection</a>
          <a class="btn ghost sm" href="<?= site_url('clients/'.$c['uid']) ?>">Open</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
