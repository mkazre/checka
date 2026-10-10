<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>VIN History Checks</h1><div class="sub">Archive of every VIN check run — specs, accident, stolen & registry data.</div></div>
  <div class="spacer"></div>
  <a class="btn" href="<?= site_url('vin-checks/new') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    Run VIN Check
  </a>
</div>

<div class="card">
  <div class="card-h">
    <h3>Archive</h3>
    <form method="get" action="<?= site_url('vin-checks') ?>" style="margin-left:auto">
      <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Search VIN, make, model…" class="mono" style="padding:7px 10px;border:1px solid var(--line);border-radius:8px;font-size:12.5px">
    </form>
  </div>
  <?php if (empty($reports)): ?>
    <div class="empty">No VIN checks yet.</div>
  <?php else: ?>
  <div class="tbl-scroll">
  <table class="tbl">
    <thead><tr><th>VIN</th><th>Vehicle</th><th>Result</th><th>Providers</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reports as $r): ?>
      <tr>
        <td class="mono" style="font-size:11px"><?= esc($r['vin']) ?></td>
        <td><?= esc(trim(($r['year']?:'').' '.($r['make']?:'').' '.($r['model']?:'')) ?: '—') ?></td>
        <td><?= esc($r['headline'] ?: '—') ?></td>
        <td class="sub" style="font-size:11.5px;color:var(--ink-3)"><?= esc($r['providers_used'] ?: '—') ?></td>
        <td class="sub" style="font-size:12px"><?= esc(date('Y-m-d H:i', strtotime($r['created_at']))) ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="<?= site_url('vin-checks/'.$r['report_uid']) ?>">View</a>
          <form method="post" action="<?= site_url('vin-checks/'.$r['report_uid'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this VIN check?')"><?= csrf_field() ?><button class="btn danger sm">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
