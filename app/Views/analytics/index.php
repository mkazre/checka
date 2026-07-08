<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
  $rag  = fn($s) => CHECKA_RATINGS[$s]['colour'] ?? '#15803d';
  $rlab = fn($s) => CHECKA_RATINGS[$s]['label'] ?? ucfirst($s);
  $completed = max(1, (int) $summary['completed']);
  $maxM = max(1, max(array_map(fn($m) => $m['count'], $monthly)));
  $maxF = max(1, max(array_map(fn($f) => (int) $f['count'], $failures ?: [['count' => 1]])));
?>

<div class="page-head">
  <div><h1>Analytics</h1><div class="sub">Workshop performance at a glance.</div></div>
</div>

<!-- KPI tiles -->
<div class="grid cols-4">
  <div class="kpi"><div class="k">Total Inspections</div><div class="v"><?= $summary['total'] ?></div></div>
  <div class="kpi"><div class="k">Completed</div><div class="v"><?= $summary['completed'] ?><span style="font-size:13px;color:var(--ink-3);font-weight:400"> · <?= $summary['draft'] ?> draft</span></div></div>
  <div class="kpi"><div class="k">Avg Condition Score</div><div class="v"><?= $summary['avgScore'] !== null ? $summary['avgScore'] : '—' ?><?php if($summary['avgScore']!==null): ?><span style="font-size:14px;color:var(--ink-3)">/100</span><?php endif; ?></div></div>
  <?php if ($summary['approved'] + $summary['declined'] > 0): ?>
    <div class="kpi"><div class="k">Client Approvals</div><div class="v" style="color:var(--ok)"><?= $summary['approved'] ?><span style="font-size:14px;color:var(--major)"> / <?= $summary['declined'] ?> declined</span></div></div>
  <?php else: ?>
    <div class="kpi"><div class="k">All-Clear Reports</div><div class="v" style="color:var(--ok)"><?= $verdict['ok'] ?? 0 ?></div></div>
  <?php endif; ?>
</div>

<div class="grid cols-2" style="margin-top:16px">
  <!-- Monthly trend -->
  <div class="card">
    <div class="card-h"><h3>Inspections · last 6 months</h3></div>
    <div class="card-b">
      <svg viewBox="0 0 600 220" width="100%" style="display:block">
        <?php $bw = 600/count($monthly); foreach ($monthly as $i => $m):
          $bh = ($m['count']/$maxM) * 160; $x = $i*$bw + $bw*0.2; $y = 180 - $bh; $w = $bw*0.6; ?>
          <rect x="<?= round($x,1) ?>" y="<?= round($y,1) ?>" width="<?= round($w,1) ?>" height="<?= round($bh,1) ?>" rx="4" fill="#37b34a" opacity="0.9"/>
          <text x="<?= round($x+$w/2,1) ?>" y="<?= round($y-6,1) ?>" text-anchor="middle" font-size="12" fill="#2d2d2d" font-weight="700"><?= $m['count'] ?></text>
          <text x="<?= round($x+$w/2,1) ?>" y="200" text-anchor="middle" font-size="11" fill="#9aa7b4"><?= esc($m['label']) ?></text>
        <?php endforeach; ?>
        <line x1="0" y1="181" x2="600" y2="181" stroke="#e6ebef" stroke-width="1"/>
      </svg>
    </div>
  </div>

  <!-- Verdict distribution -->
  <div class="card">
    <div class="card-h"><h3>Verdict distribution</h3></div>
    <div class="card-b">
      <?php foreach (['ok','minor','lesser','major'] as $s): $c = (int) ($verdict[$s] ?? 0); $pct = round($c/$completed*100); ?>
        <div style="margin-bottom:12px">
          <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
            <span style="font-weight:600;color:<?= $rag($s) ?>"><?= esc($rlab($s)) ?></span>
            <span class="mono" style="color:var(--ink-3)"><?= $c ?> · <?= $pct ?>%</span>
          </div>
          <div style="height:10px;background:#eef1f3;border-radius:6px;overflow:hidden">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $rag($s) ?>;border-radius:6px"></div>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="sub" style="font-size:11.5px;color:var(--ink-3);margin-top:6px">Across <?= $summary['completed'] ?> completed inspection<?= $summary['completed']==1?'':'s' ?>.</div>
    </div>
  </div>
</div>

<div class="grid cols-2" style="margin-top:16px">
  <!-- Top failures -->
  <div class="card">
    <div class="card-h"><h3>Most common findings</h3></div>
    <?php if (empty($failures)): ?>
      <div class="empty">No lesser/major findings recorded yet.</div>
    <?php else: ?>
      <div class="card-b">
        <?php foreach ($failures as $f): $pct = round((int)$f['count']/$maxF*100); ?>
          <div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px"><span><?= esc($f['name']) ?></span><span class="mono" style="color:var(--ink-3)"><?= $f['count'] ?></span></div>
            <div style="height:8px;background:#eef1f3;border-radius:5px;overflow:hidden"><div style="height:100%;width:<?= $pct ?>%;background:var(--lesser);border-radius:5px"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Mechanic productivity -->
  <div class="card">
    <div class="card-h"><h3>Inspector productivity</h3></div>
    <?php if (empty($mechanics)): ?>
      <div class="empty">No completed inspections yet.</div>
    <?php else: ?>
      <div class="tbl-scroll">
      <table class="tbl">
        <thead><tr><th>Inspector</th><th>Inspections</th><th>Avg score</th></tr></thead>
        <tbody>
          <?php foreach ($mechanics as $m): ?>
            <tr>
              <td><b><?= esc($m['name']) ?></b></td>
              <td class="mono"><?= $m['count'] ?></td>
              <td class="mono"><?= $m['avg'] !== null ? $m['avg'].'/100' : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
