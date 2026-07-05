<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div>
    <h1>Workshop Dashboard</h1>
    <div class="sub">Every inspection — capture, view, edit, report.</div>
  </div>
  <div class="spacer"></div>
  <a class="btn" href="<?= site_url('inspections/new') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> New Inspection
  </a>
</div>

<div class="grid cols-4" style="margin-bottom:20px">
  <div class="kpi"><div class="k">Total Inspections</div><div class="v"><?= $counts['total'] ?></div></div>
  <div class="kpi"><div class="k">In Progress</div><div class="v" style="color:var(--lesser)"><?= $counts['draft'] ?></div></div>
  <div class="kpi"><div class="k">Completed</div><div class="v" style="color:var(--ok)"><?= $counts['completed'] ?></div></div>
  <div class="kpi"><div class="k">Avg Condition</div><div class="v"><?= $counts['avgScore'] !== null ? $counts['avgScore'] : '—' ?></div></div>
</div>

<div class="card">
  <div class="card-h">
    <h3>Inspections</h3>
    <div style="margin-left:auto;display:flex;gap:6px">
      <?php $tabs = ['' => 'All', 'draft' => 'In Progress', 'completed' => 'Completed']; ?>
      <?php foreach ($tabs as $key => $label): ?>
        <a class="btn ghost sm <?= ($status ?? '') === $key ? '' : '' ?>"
           href="<?= site_url('dashboard') . ($key ? '?status=' . $key : '') ?>"
           style="<?= ($status ?? '') === $key ? 'background:var(--ink);color:#fff;border-color:var(--ink)' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
      <div>No inspections yet. Start your first one.</div>
      <div style="margin-top:12px"><a class="btn" href="<?= site_url('inspections/new') ?>">New Inspection</a></div>
    </div>
  <?php else: ?>
    <table class="tbl">
      <thead><tr><th>Vehicle</th><th>Reg / VIN</th><th>Date</th><th>Score</th><th>Verdict</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php $vs = $r['verdict_status'] ?: 'ok'; ?>
        <tr>
          <td>
            <div style="font-weight:600;font-family:var(--display)"><?= esc(trim(($r['year']?:'').' '.($r['make']?:'').' '.($r['model']?:'')) ?: 'Vehicle') ?></div>
          </td>
          <td class="mono" style="font-size:12px;color:var(--ink-2)"><?= esc($r['plate'] ?: $r['vin'] ?: '—') ?></td>
          <td class="mono" style="font-size:12.5px"><?= esc($r['inspection_date'] ?: date('Y-m-d', strtotime($r['created_at']))) ?></td>
          <td style="font-family:var(--display);font-weight:700;font-size:16px"><?= $r['score'] !== null ? $r['score'] : '—' ?></td>
          <td><?php if ($r['verdict_text']): ?><span class="status <?= $vs ?>"><span class="dot"></span><?= esc($r['verdict_text']) ?></span><?php else: ?>—<?php endif; ?></td>
          <td><span class="pill <?= $r['status'] ?>"><?= $r['status'] === 'completed' ? 'Completed' : 'In Progress' ?></span></td>
          <td style="text-align:right;white-space:nowrap">
            <a class="btn ghost sm" href="<?= site_url('inspections/' . $r['report_uid'] . '/capture') ?>">Edit</a>
            <a class="btn ghost sm" href="<?= site_url('reports/' . $r['report_uid']) ?>">Report</a>
            <form method="post" action="<?= site_url('inspections/' . $r['report_uid'] . '/delete') ?>" style="display:inline"
                  onsubmit="return confirm('Delete this inspection? This cannot be undone.')">
              <?= csrf_field() ?>
              <button class="btn danger sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
