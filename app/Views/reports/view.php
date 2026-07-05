<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
  .sheet{max-width:900px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden;box-shadow:0 12px 40px rgba(16,23,29,.07)}
  .dash{background:var(--panel);color:#e9eef2;padding:24px 28px}
  .dash-top{display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;align-items:flex-start}
  .vehicle-id .yr{font-family:var(--mono);font-size:11px;letter-spacing:.24em;color:#7f8ea0;text-transform:uppercase}
  .vehicle-id h2{font-size:30px;font-weight:600;margin:2px 0 6px}
  .vehicle-id .vin{font-family:var(--mono);font-size:12px;color:#aeb9c6}
  .verdict{text-align:right}
  .verdict .label{font-size:10px;letter-spacing:.22em;text-transform:uppercase;color:#7f8ea0}
  .verdict .badge{display:inline-flex;align-items:center;gap:9px;margin-top:6px;padding:9px 15px;border-radius:999px;font-family:var(--display);font-weight:600;font-size:15px}
  .verdict .badge .dot{width:11px;height:11px;border-radius:50%}
  .gauges{display:grid;grid-template-columns:170px 1fr;gap:26px;margin-top:22px;align-items:center}
  .score-gauge{position:relative;width:170px;height:100px}
  .score-gauge .num{position:absolute;left:0;right:0;top:46px;text-align:center;font-family:var(--display);font-weight:700;font-size:34px}
  .score-gauge .cap{position:absolute;left:0;right:0;top:82px;text-align:center;font-size:9.5px;letter-spacing:.2em;text-transform:uppercase;color:#7f8ea0}
  .sev-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
  .sev{background:var(--panel-2);border:1px solid #2b3742;border-radius:9px;padding:12px;position:relative;overflow:hidden}
  .sev::before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px}
  .sev.ok::before{background:var(--ok)}.sev.minor::before{background:#4a80bd}.sev.lesser::before{background:var(--lesser)}.sev.major::before{background:var(--major)}
  .sev .n{font-family:var(--mono);font-size:26px;font-weight:700}
  .sev .t{font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:#9aa7b4;margin-top:6px}
  .rsection{padding:20px 28px;border-top:1px solid var(--line)}
  .rsection-head{display:flex;align-items:baseline;gap:12px;margin-bottom:12px}
  .rsection-head .idx{font-family:var(--mono);font-size:12px;font-weight:700;color:#fff;background:var(--ink);border-radius:5px;padding:3px 8px}
  .rsection-head h3{font-size:15px;font-weight:600}
  .rsection-head .tally{margin-left:auto;display:flex;gap:6px}
  .mini-chip{font-family:var(--mono);font-size:10px;font-weight:700;padding:2px 7px;border-radius:5px;border:1px solid}
  .mini-chip.minor{color:var(--minor);background:var(--minor-bg);border-color:var(--minor-line)}
  .mini-chip.lesser{color:var(--lesser);background:var(--lesser-bg);border-color:var(--lesser-line)}
  .mini-chip.major{color:var(--major);background:var(--major-bg);border-color:var(--major-line)}
  table.checks{width:100%;border-collapse:collapse}
  table.checks th{text-align:left;font-family:var(--mono);font-size:9.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3);font-weight:700;padding:0 10px 7px;border-bottom:1px solid var(--line)}
  table.checks td{padding:9px 10px;border-bottom:1px solid var(--line);vertical-align:top}
  .ref{font-family:var(--mono);font-size:11px;color:var(--ink-3);white-space:nowrap}
  .item-name.sub{padding-left:16px;color:var(--ink-2)}
  .comment{color:var(--ink-2);font-size:12.5px}
  .gallery{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:12px}
  .gallery a{aspect-ratio:1/1;max-width:300px;border-radius:7px;overflow:hidden;border:1px solid var(--line);background:#f2f5f7;display:flex;align-items:center;justify-content:center}
  .gallery img{max-width:100%;max-height:100%;object-fit:contain}
  .detail-grid{display:grid;grid-template-columns:repeat(3,1fr);border:1px solid var(--line);border-radius:10px;overflow:hidden}
  .detail-grid .cell{padding:12px 14px;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}
  .detail-grid .cell .k{font-family:var(--mono);font-size:9.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--ink-3)}
  .detail-grid .cell .v{font-weight:600;margin-top:3px}
  .feature-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
  .fcard{border:1px solid var(--line);border-radius:10px;padding:16px 18px}
  .fcard h4{font-size:12px;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;color:var(--ink-2)}
  .tyres{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
  .tyre{border:1px solid var(--line);border-radius:9px;padding:11px 12px}
  .tyre .pos{font-family:var(--mono);font-size:9.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--ink-3)}
  .tyre .tread{font-family:var(--display);font-weight:700;font-size:22px}
  .tyre .meta{font-family:var(--mono);font-size:10px;color:var(--ink-2);margin-top:6px}
  .bar{height:5px;border-radius:3px;background:#e6ebef;margin-top:8px;overflow:hidden}.bar>i{display:block;height:100%}
  .legend-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
  .lg{border:1px solid var(--line);border-radius:9px;padding:10px 12px;border-left-width:4px}
  .lg.ok{border-left-color:var(--ok)}.lg.minor{border-left-color:var(--minor)}.lg.lesser{border-left-color:var(--lesser)}.lg.major{border-left-color:var(--major)}
  .lg .h{font-weight:700;font-size:12px}.lg .d{font-size:10.5px;color:var(--ink-3);margin-top:2px}
  @media(max-width:760px){.gauges,.feature-row,.legend-strip{grid-template-columns:1fr}.sev-grid{grid-template-columns:repeat(2,1fr)}.detail-grid{grid-template-columns:1fr 1fr}.tyres{grid-template-columns:1fr}}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
  $v = $report['vehicle'] ?? []; $ins = $report['inspection']; $meta = $report['meta'] ?? [];
  $vs = $compute['verdict']['status']; $counts = $compute['counts'];
  $rag = fn($s)=>CHECKA_RATINGS[$s]['colour'];
  $chip = fn($s)=>'<span class="status '.$s.'"><span class="dot"></span>'.esc(CHECKA_RATINGS[$s]['label']).'</span>';
  $circ = M_PI*76; $dash = ($compute['score']/100)*$circ;
?>

<div class="page-head">
  <div><h1>Inspection Report</h1><div class="sub mono"><?= esc(substr($uid,0,8)) ?> · <?= esc($ins['status']==='completed'?'Completed':'Draft') ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('inspections/'.$uid.'/capture') ?>">Edit</a>
  <a class="btn dark" href="<?= site_url('reports/'.$uid.'/pdf') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg> Download PDF
  </a>
</div>

<div class="sheet">
  <!-- Dashboard band -->
  <div class="dash">
    <div class="dash-top">
      <div class="vehicle-id">
        <div class="yr"><?= esc(($v['year']??'').' · '.($v['colour']??'')) ?></div>
        <h2><?= esc(trim(($v['make']??'').' '.($v['model']??'')) ?: 'Vehicle') ?></h2>
        <div class="vin">VIN <?= esc($v['vin'] ?? '—') ?></div>
      </div>
      <div class="verdict">
        <div class="label">Overall Verdict</div>
        <div class="badge" style="background:<?= $rag($vs) ?>1f;color:<?= $rag($vs) ?>;box-shadow:inset 0 0 0 1px <?= $rag($vs) ?>55">
          <span class="dot" style="background:<?= $rag($vs) ?>"></span><?= esc($compute['verdict']['text']) ?>
        </div>
      </div>
    </div>
    <div class="gauges">
      <div class="score-gauge">
        <svg width="170" height="100" viewBox="0 0 170 100">
          <path d="M9 88 A76 76 0 0 1 161 88" fill="none" stroke="#2b3742" stroke-width="11" stroke-linecap="round"/>
          <path d="M9 88 A76 76 0 0 1 161 88" fill="none" stroke="<?= $rag($vs) ?>" stroke-width="11" stroke-linecap="round" stroke-dasharray="<?= $dash ?> <?= $circ ?>"/>
        </svg>
        <div class="num" style="color:<?= $rag($vs) ?>"><?= $compute['score'] ?></div>
        <div class="cap">Condition Score</div>
      </div>
      <div class="sev-grid">
        <div class="sev ok"><div class="n"><?= $counts['ok'] ?></div><div class="t">No Issues</div></div>
        <div class="sev minor"><div class="n"><?= $counts['minor'] ?></div><div class="t">Minor</div></div>
        <div class="sev lesser"><div class="n"><?= $counts['lesser'] ?></div><div class="t">Lesser</div></div>
        <div class="sev major"><div class="n"><?= $counts['major'] ?></div><div class="t">Major</div></div>
      </div>
    </div>
  </div>

  <!-- Vehicle details -->
  <div class="rsection">
    <div class="rsection-head"><span class="idx">i</span><h3>Vehicle Details</h3></div>
    <div class="detail-grid">
      <div class="cell"><div class="k">Inspection Date</div><div class="v"><?= esc($ins['inspection_date']) ?></div></div>
      <div class="cell"><div class="k">Odometer</div><div class="v"><?= esc($meta['km_before'] ?? $v['odometer'] ?? '—') ?></div></div>
      <div class="cell"><div class="k">Year</div><div class="v"><?= esc($v['year'] ?? '—') ?></div></div>
      <div class="cell"><div class="k">Make / Model</div><div class="v"><?= esc(trim(($v['make']??'').' '.($v['model']??''))) ?></div></div>
      <div class="cell"><div class="k">Colour</div><div class="v"><?= esc($v['colour'] ?? '—') ?></div></div>
      <div class="cell"><div class="k">Registration</div><div class="v mono"><?= esc($v['plate'] ?? '—') ?></div></div>
      <div class="cell"><div class="k">VIN</div><div class="v mono" style="font-size:11.5px"><?= esc($v['vin'] ?? '—') ?></div></div>
      <div class="cell"><div class="k">Dealer / Seller</div><div class="v"><?= esc($meta['dealer'] ?? $v['seller'] ?? '—') ?></div></div>
      <div class="cell"><div class="k">Mechanic</div><div class="v"><?= esc($meta['mechanic'] ?? '—') ?></div></div>
    </div>
  </div>

  <!-- Condition at a glance -->
  <div class="rsection">
    <div class="rsection-head"><span class="idx">◐</span><h3>Condition At A Glance</h3></div>
    <div class="feature-row">
      <div class="fcard">
        <h4>Body Damage Map</h4>
        <?php $z = $compute['bodyZones']; $zc = fn($k)=>$rag($z[$k] ?? 'ok'); ?>
        <div style="display:flex;gap:16px;align-items:center">
          <svg width="130" height="230" viewBox="0 0 130 230">
            <rect x="34" y="70" width="62" height="90" rx="14" fill="<?= $zc('roof') ?>" stroke="#fff" stroke-width="1.5"/>
            <path d="M34 70 q-10 -34 31 -50 q41 16 31 50 z" fill="<?= $zc('front') ?>" stroke="#fff" stroke-width="1.5"/>
            <path d="M34 160 q-10 34 31 50 q41 -16 31 -50 z" fill="<?= $zc('back') ?>" stroke="#fff" stroke-width="1.5"/>
            <rect x="20" y="78" width="16" height="74" rx="7" fill="<?= $zc('left') ?>" stroke="#fff" stroke-width="1.5"/>
            <rect x="94" y="78" width="16" height="74" rx="7" fill="<?= $zc('right') ?>" stroke="#fff" stroke-width="1.5"/>
            <text x="65" y="40" text-anchor="middle" font-size="8" fill="#fff">FRONT</text>
            <text x="65" y="196" text-anchor="middle" font-size="8" fill="#fff">REAR</text>
          </svg>
          <div style="font-size:11px;color:var(--ink-2);line-height:1.9">
            <?php foreach (['ok'=>'No issues','minor'=>'Minor','lesser'=>'Lesser','major'=>'Major'] as $k=>$lbl): ?>
              <div style="display:flex;align-items:center;gap:8px"><span style="width:12px;height:12px;border-radius:3px;background:<?= $rag($k) ?>"></span> <?= $lbl ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="fcard">
        <h4>Tyre Health — Tread &amp; Age</h4>
        <?php if (empty($compute['tyres'])): ?><div class="mono" style="font-size:12px;color:var(--ink-3)">No tyre readings captured.</div><?php else: ?>
        <div class="tyres">
          <?php foreach ($compute['tyres'] as $t): $tf=$t['tstat']['flag']; $pct=min(100,($t['tread']/8)*100); ?>
            <div class="tyre">
              <div class="pos"><?= esc($t['pos']) ?></div>
              <div class="tread" style="color:<?= $rag($t['worst']) ?>"><?= number_format($t['tread'],2) ?><small style="font-size:11px;color:var(--ink-3)"> mm</small></div>
              <div class="bar"><i style="width:<?= $pct ?>%;background:<?= $rag($tf) ?>"></i></div>
              <div class="meta">DOT <?= esc($t['dot'] ?: '—') ?><?= $t['dotInfo'] && $t['dotInfo']['age'] ? ' · '.$t['dotInfo']['age'].' yrs' : '' ?> · <?= esc($t['tstat']['text']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Summary of findings -->
  <?php if (! empty($compute['findings'])): ?>
  <div class="rsection">
    <div class="rsection-head"><span class="idx">!</span><h3>Summary of Findings</h3></div>
    <table class="checks">
      <thead><tr><th>Ref</th><th>Item</th><th>Result</th><th>Comment</th></tr></thead>
      <tbody>
      <?php foreach ($compute['findings'] as $f): ?>
        <tr>
          <td class="ref"><?= esc($f['ref']) ?></td>
          <td><?= esc(preg_replace('/ Checks?$/','',$f['section_title'] ?? '')) ?> » <?= esc($f['name']) ?></td>
          <td><?= $chip($f['status']) ?></td>
          <td class="comment"><?= esc($f['comment']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- All sections -->
  <?php foreach ($report['sections'] as $section): ?>
    <?php
      $tc=['minor'=>0,'lesser'=>0,'major'=>0]; $photos=[];
      foreach ($section['items'] as $it){ if($it['type']==='rating' && isset($tc[$it['status']])) $tc[$it['status']]++; foreach($it['photos'] as $p) $photos[]=$p; }
    ?>
    <div class="rsection">
      <div class="rsection-head">
        <span class="idx"><?= $section['idx'] ?></span><h3><?= esc($section['title']) ?></h3>
        <span class="tally">
          <?php foreach (['major','lesser','minor'] as $k){ if($tc[$k]) echo '<span class="mini-chip '.$k.'">'.$tc[$k].' '.ucfirst($k).'</span>'; } ?>
        </span>
      </div>
      <table class="checks">
        <thead><tr><th style="width:52px">Ref</th><th>Item</th><th style="width:132px">Result</th><th>Comment</th></tr></thead>
        <tbody>
        <?php foreach ($section['items'] as $item): ?>
          <tr>
            <td class="ref"><?= esc($item['ref']) ?></td>
            <td><span class="item-name <?= $item['sub']?'sub':'' ?>"><?= esc($item['name']) ?></span></td>
            <td><?= $item['type']==='rating' ? $chip($item['status']) : '<span class="mono" style="font-size:11px;color:var(--ink-3)">'.esc(strtoupper($item['type'])).'</span>' ?></td>
            <td class="comment"><?php if (! empty($item['choice'])): ?><span style="display:inline-block;font-family:var(--mono);font-size:10px;font-weight:700;background:#eef1f3;color:var(--ink-2);border:1px solid var(--line);border-radius:5px;padding:1px 6px;margin-right:6px"><?= esc($item['choice']) ?></span><?php endif; ?><?= esc($item['comment'] ?: $item['text']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($photos): ?>
        <div class="gallery">
          <?php foreach (array_slice($photos,0,8) as $p): ?>
            <a href="<?= $p['url'] ?>" target="_blank"><img src="<?= $p['thumb'] ?>" alt=""></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <!-- Conclusion + verify -->
  <div class="rsection">
    <div class="rsection-head"><span class="idx">✓</span><h3>Vehicle Conclusion</h3></div>
    <div style="background:#f7f9fa;border:1px solid var(--line);border-radius:10px;padding:16px 18px">
      <p style="margin:0;color:var(--ink-2);font-size:12.5px;line-height:1.65"><?= esc($ins['conclusion']) ?></p>
      <div style="display:grid;grid-template-columns:1fr 220px;gap:24px;margin-top:16px;align-items:end">
        <div style="border-top:1.5px solid var(--ink);padding-top:6px;font-size:11px;color:var(--ink-3)">
          <b style="display:block;color:var(--ink);font-size:13px;font-family:var(--display)"><?= esc($meta['mechanic'] ?? 'Inspecting Mechanic') ?></b>
          Inspecting Mechanic · signature on file
        </div>
        <div style="border:1px solid var(--line);border-radius:10px;padding:14px;text-align:center">
          <div id="qr" style="width:104px;height:104px;margin:0 auto 8px"></div>
          <div style="font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink-3)">Scan to Verify</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Legend -->
  <div class="rsection">
    <div class="rsection-head"><span class="idx">◧</span><h3>Colour Rating Key</h3></div>
    <div class="legend-strip">
      <div class="lg ok"><div class="h">Green · No Issues</div><div class="d">No issues found</div></div>
      <div class="lg minor"><div class="h">Blue · Minor</div><div class="d">Not 100% up to standard — no repair needed</div></div>
      <div class="lg lesser"><div class="h">Orange · Lesser</div><div class="d">If repaired, approved for finance / purchase</div></div>
      <div class="lg major"><div class="h">Red · Major</div><div class="d">Not fit to be financed / purchased</div></div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
  try { new QRCode(document.getElementById("qr"), {text:"<?= esc($report['verifyUrl'], 'js') ?>", width:104, height:104, colorDark:"#12171d", colorLight:"#fff"}); } catch(e){}
</script>
<?= $this->endSection() ?>
