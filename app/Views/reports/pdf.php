<?php
  $v = $report['vehicle'] ?? []; $ins = $report['inspection']; $meta = $report['meta'] ?? [];
  $vs = $compute['verdict']['status']; $counts = $compute['counts'];
  $rag = fn($s)=>CHECKA_RATINGS[$s]['colour'] ?? '#15803d';
  $bg  = fn($s)=>CHECKA_RATINGS[$s]['bg'] ?? '#e7f6ec';
  $label = fn($s)=>CHECKA_RATINGS[$s]['label'] ?? 'No Issues';
  $ev  = fn($k)=>esc($v[$k] ?? '');
  $sigFile = $ins['signature_path'] ? $uploadDir . $ins['signature_path'] : null;

  // Photo strip (first 5 non-video photos) + thumbnail path helper
  $thumbAbs = function($p) use ($uploadDir){
    $tp = $p['thumb_path'] ?? (dirname($p['path']).'/thumb_'.basename($p['path']));
    $abs = $uploadDir.$tp;
    if (! is_file($abs)) { $abs = $uploadDir.$p['path']; }
    return is_file($abs) ? $abs : null;
  };
  $strip = [];
  foreach ($report['sections'] as $sctn) { foreach ($sctn['items'] as $it) { foreach ($it['photos'] as $p) { if (! $p['video']) { $strip[] = $p; } } } }
  $strip = array_slice($strip, 0, 5);
?>
<style>
  body{font-family:sans-serif;font-size:9.5pt;color:#12171d}
  h1,h2,h3{margin:0}
  /* ---- profile page ---- */
  .pf-brand{font-size:15pt;font-weight:bold;color:#12171d}
  .pf-brand span{font-size:7.5pt;color:#6b7682;letter-spacing:2px;text-transform:uppercase;font-weight:normal}
  .pf-title{font-size:12pt;font-weight:bold;letter-spacing:1px;text-align:center}
  .pf-rep{font-family:sans-serif;font-size:8pt;color:#3d4753;text-align:right;line-height:1.5}
  .pf-rep b{color:#12171d}
  .pf-sub td{font-size:9pt;padding:6px 4px}
  .sec2{background:#f1f2f3;font-weight:bold;font-size:10pt;padding:5px 8px;border-left:4px solid #37b34a;color:#2a8f3a;margin-top:12px}
  .vd{width:100%;border-collapse:collapse;margin-top:4px}
  .vd td{font-size:9pt;padding:4px 6px;border-bottom:1px solid #eef1f3}
  .vd .k{color:#6b7682;width:16%}
  .vd .v{font-weight:bold;width:34%}
  .strip td{padding:0 2px}
  .strip img{width:100px;height:auto;border:1px solid #d6dce1;border-radius:3px}
  /* ---- report pages ---- */
  .band{background:#37b34a;color:#fff;padding:16px 18px}
  .band .yr{font-size:8pt;color:#dff3e5;letter-spacing:2px;text-transform:uppercase}
  .band h2{font-size:20pt;margin:2px 0;color:#fff}
  .band .vin{font-size:8.5pt;color:#eaf7ee}
  .band .vlabel{font-size:7pt;color:#dff3e5;text-transform:uppercase;letter-spacing:1px}
  .verdict-badge{background:#ffffff;border-radius:6px;padding:6px 12px;font-weight:bold;font-size:10.5pt;display:inline-block}
  .score-big{font-size:23pt;font-weight:bold;color:#fff;margin-top:7px}
  .score-big span{font-size:9pt;color:#dff3e5;font-weight:normal}
  .metrics td{width:25%;text-align:center;border:1px solid #d6dce1;background:#ffffff;color:#12171d;padding:9px}
  .metrics .n{font-size:16pt;font-weight:bold}
  .metrics .t{font-size:7pt;color:#6b7682;text-transform:uppercase;letter-spacing:1px}
  h3.sec{font-size:11pt;margin:16px 0 6px;padding-bottom:3px;border-bottom:2px solid #37b34a}
  table.checks{width:100%;border-collapse:collapse;margin-top:4px}
  table.checks th{font-size:7pt;text-transform:uppercase;letter-spacing:.5px;color:#6b7682;text-align:left;border-bottom:1px solid #d6dce1;padding:4px 6px}
  table.checks td{padding:5px 6px;border-bottom:1px solid #eef1f3;vertical-align:top;font-size:9pt}
  .ref{color:#6b7682;font-size:8.5pt}
  .chip{padding:2px 7px;border-radius:10px;font-size:8pt;font-weight:bold}
  .detail td{border:1px solid #d6dce1;padding:6px 8px;width:33%}
  .detail .k{font-size:7pt;text-transform:uppercase;color:#6b7682}
  .detail .v{font-weight:bold}
  .concl{background:#f7f9fa;border:1px solid #d6dce1;padding:12px}
  .dmg-name{color:#b42318;font-weight:bold}
</style>

<!-- ============================ PAGE 1 · VEHICLE PROFILE ============================ -->
<table width="100%" style="border-bottom:2px solid #2d2d2d;padding-bottom:6px"><tr>
  <td width="42%"><?php $logoFile = FCPATH . 'assets/img/logo.png'; if (is_file($logoFile)): ?><img src="<?= $logoFile ?>" style="height:52px"><?php else: ?><span class="pf-brand">CheckA+<br><span>by WheelsTurn</span></span><?php endif; ?></td>
  <td width="30%" class="pf-title">VEHICLE<br>CONDITION PROFILE</td>
  <td width="28%" class="pf-rep">
    <table width="100%"><tr>
      <td align="right"><barcode code="<?= esc($report['verifyUrl']) ?>" type="QR" class="barcode" size="0.7" error="M" /></td>
      <td width="2"></td>
      <td align="right">Report No<br><b><?= esc(substr($ins['report_uid'],0,10)) ?></b><br>Date<br><b><?= esc($ins['inspection_date'] ?: date('Y-m-d')) ?></b></td>
    </tr></table>
  </td>
</tr></table>

<table width="100%" class="pf-sub" style="border-bottom:1px solid #d6dce1"><tr>
  <td width="55%"><span style="color:#6b7682">Client</span> &nbsp; <b><?= $ev('client') ?: esc($meta['dealer'] ?? '—') ?></b></td>
  <td width="45%" align="right"><span style="color:#6b7682">Inspection Location</span> &nbsp; <b><?= $ev('inspection_location') ?: '—' ?></b></td>
</tr></table>

<?php if (! empty($stockImage)): ?>
<table width="100%" style="margin-top:8px"><tr>
  <td align="center" style="background:#fafbfc;border:1px solid #d6dce1;border-radius:6px;padding:8px">
    <img src="<?= $stockImage ?>" style="max-height:200px;width:auto">
    <div style="font-family:monospace;font-size:7pt;color:#6b7682;letter-spacing:1px;text-transform:uppercase;margin-top:4px">Reference · ideal condition</div>
  </td>
</tr></table>
<?php endif; ?>

<?php if ($strip): ?>
<table width="100%" class="strip" style="margin-top:8px"><tr>
  <?php $i=0; foreach ($strip as $p): $a=$thumbAbs($p); if($a): ?><td align="center"><img src="<?= $a ?>"></td><?php $i++; endif; endforeach; for(;$i<5;$i++) echo '<td></td>'; ?>
</tr></table>
<?php endif; ?>

<div class="sec2">Assignment</div>
<table class="vd">
  <tr><td class="k">Client</td><td class="v"><?= $ev('client') ?: esc($meta['dealer'] ?? '—') ?></td>
      <td class="k">Inspection Date</td><td class="v"><?= esc($ins['inspection_date'] ?: '—') ?></td></tr>
  <tr><td class="k">Inspection Location</td><td class="v" colspan="3"><?= $ev('inspection_location') ?: '—' ?></td></tr>
</table>

<div class="sec2">Vehicle Data</div>
<table class="vd">
  <tr><td class="k">Vehicle Type</td><td class="v"><?= $ev('vehicle_type') ?: '—' ?></td><td class="k">Registered Owner</td><td class="v"><?= $ev('registered_owner') ?: '—' ?></td></tr>
  <tr><td class="k">Manufacturer</td><td class="v"><?= $ev('make') ?: '—' ?></td><td class="k">Registration No.</td><td class="v"><?= $ev('plate') ?: '—' ?></td></tr>
  <tr><td class="k">Model</td><td class="v"><?= $ev('model') ?: '—' ?></td><td class="k">Register Number</td><td class="v"><?= $ev('register_number') ?: '—' ?></td></tr>
  <tr><td class="k">Derivative</td><td class="v"><?= $ev('derivative') ?: '—' ?></td><td class="k">Licence No.</td><td class="v"><?= $ev('licence_no') ?: '—' ?></td></tr>
  <tr><td class="k">VIN</td><td class="v"><?= $ev('vin') ?: '—' ?></td><td class="k">Date First Licensing</td><td class="v"><?= $ev('date_first_licensing') ?: '—' ?></td></tr>
  <tr><td class="k">Engine Number</td><td class="v"><?= $ev('engine_number') ?: '—' ?></td><td class="k">Last COR</td><td class="v"><?= $ev('last_cor') ?: '—' ?></td></tr>
  <tr><td class="k">Doors</td><td class="v"><?= $ev('doors') ?: '—' ?></td><td class="k">Licence Expiry</td><td class="v"><?= $ev('licence_expiry') ?: '—' ?></td></tr>
  <tr><td class="k">Color</td><td class="v"><?= $ev('colour') ?: '—' ?></td><td class="k">Power / Displacement</td><td class="v"><?= $ev('power_displacement') ?: '—' ?></td></tr>
  <tr><td class="k">Year Model</td><td class="v"><?= $ev('year') ?: '—' ?></td><td class="k">Paint Finish</td><td class="v"><?= $ev('paint_finish') ?: '—' ?></td></tr>
  <tr><td class="k">Km Reading</td><td class="v"><?= $ev('odometer') ?: esc($meta['km_before'] ?? '—') ?></td><td class="k">Fuel</td><td class="v"><?= $ev('fuel') ?: '—' ?></td></tr>
  <tr><td class="k">Cab / Body Type</td><td class="v"><?= $ev('body_type') ?: '—' ?></td><td class="k">Transmission</td><td class="v"><?= $ev('transmission') ?: '—' ?></td></tr>
  <tr><td class="k">GVM</td><td class="v"><?= $ev('gvm') ?: '—' ?></td><td class="k">Seating</td><td class="v"><?= $ev('seating') ?: '—' ?></td></tr>
  <tr><td class="k">Tare</td><td class="v"><?= $ev('tare') ?: '—' ?></td><td class="k">Date of Test</td><td class="v"><?= $ev('date_of_test') ?: '—' ?></td></tr>
</table>

<div class="sec2">Vehicle Options &amp; Accessories</div>
<div style="font-size:9pt;padding:6px 2px;line-height:1.5"><?= $ev('options') ?: '<span style="color:#6b7682">None recorded.</span>' ?></div>

<div class="sec2">Damage &amp; Findings</div>
<?php if (empty($compute['findings'])): ?>
  <div style="font-size:9pt;color:#6b7682;padding:6px 2px">No findings recorded — vehicle is all-clear on the latest inspection.</div>
<?php else: ?>
<table class="checks" cellspacing="0">
  <thead><tr><th width="12%">Ref</th><th width="46%">Damage</th><th>Repair Way / Comment</th></tr></thead>
  <tbody>
  <?php foreach ($compute['findings'] as $f): ?>
    <tr><td class="ref"><?= esc($f['ref']) ?></td><td class="dmg-name"><?= esc($f['name']) ?></td><td><?= esc($f['comment'] ?: '—') ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<pagebreak />

<!-- ============================ PAGE 2+ · INSPECTION REPORT ============================ -->
<div class="band">
  <table width="100%"><tr>
    <td>
      <div class="yr"><?= esc(($v['year']??'').' · '.($v['colour']??'')) ?></div>
      <h2><?= esc(trim(($v['make']??'').' '.($v['model']??'')) ?: 'Vehicle') ?></h2>
      <div class="vin">VIN <?= esc($v['vin'] ?? '—') ?> &nbsp;·&nbsp; <?= esc($v['plate'] ?? '') ?></div>
    </td>
    <td align="right">
      <div class="vlabel">Overall Verdict</div>
      <div class="verdict-badge" style="color:<?= $rag($vs) ?>;border-left:5px solid <?= $rag($vs) ?>;margin-top:4px"><?= esc($compute['verdict']['text']) ?></div>
      <div class="score-big"><?= $compute['score'] ?><span>/100</span></div>
    </td>
  </tr></table>
</div>

<table class="metrics" width="100%" cellspacing="0" style="margin-top:2px"><tr>
  <td><div class="n" style="color:#2a8f3a"><?= $counts['ok'] ?></div><div class="t">No Issues</div></td>
  <td><div class="n" style="color:#2f6fb0"><?= $counts['minor'] ?></div><div class="t">Minor</div></td>
  <td><div class="n" style="color:#c98a1e"><?= $counts['lesser'] ?></div><div class="t">Lesser</div></td>
  <td><div class="n" style="color:#b42318"><?= $counts['major'] ?></div><div class="t">Major</div></td>
</tr></table>

<?php foreach ($report['sections'] as $section): ?>
  <?php $photos=[]; foreach($section['items'] as $it){ foreach($it['photos'] as $p){ if(!$p['video']) $photos[]=$p; } } ?>
  <h3 class="sec"><?= $section['idx'] ?>. <?= esc($section['title']) ?></h3>
  <table class="checks" cellspacing="0">
    <thead><tr><th width="8%">Ref</th><th width="42%">Item</th><th width="18%">Result</th><th>Comment</th></tr></thead>
    <tbody>
    <?php foreach ($section['items'] as $item): $s=$item['status']; ?>
      <tr>
        <td class="ref"><?= esc($item['ref']) ?></td>
        <td style="<?= $item['sub']?'padding-left:16px;color:#3d4753':'' ?>"><?= esc($item['name']) ?></td>
        <td><?php if($item['type']==='rating'): ?><span class="chip" style="background:<?= $bg($s) ?>;color:<?= $rag($s) ?>"><?= esc($label($s)) ?></span><?php else: ?><span class="ref"><?= esc(strtoupper($item['type'])) ?></span><?php endif; ?></td>
        <td><?php if(!empty($item['choice'])): ?><b><?= esc($item['choice']) ?></b><?php if($item['comment']): ?> — <?php endif; ?><?php endif; ?><?= esc($item['comment'] ?: (empty($item['choice']) ? $item['text'] : '')) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($photos): ?>
    <table width="100%" cellspacing="0" cellpadding="3" style="margin-top:4px">
      <?php foreach (array_chunk($photos, 4) as $row): ?>
        <tr>
          <?php for ($c = 0; $c < 4; $c++): ?>
            <td width="25%" style="text-align:center;vertical-align:top">
              <?php if (isset($row[$c])): $a = $thumbAbs($row[$c]); if ($a): ?>
                  <img src="<?= $a ?>" style="width:125px;height:auto">
              <?php endif; endif; ?>
            </td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php endforeach; ?>

<h3 class="sec">Vehicle Conclusion</h3>
<div class="concl">
  <p style="margin:0;color:#3d4753;font-size:9pt;line-height:1.5"><?= esc($ins['conclusion']) ?></p>
  <table width="100%" style="margin-top:12px"><tr>
    <td valign="bottom">
      <?php if ($sigFile && is_file($sigFile)): ?><img src="<?= $sigFile ?>" style="height:44px"><br><?php endif; ?>
      <div style="border-top:1px solid #12171d;padding-top:3px;font-size:8pt;color:#6b7682"><b style="font-size:10pt;color:#12171d"><?= esc($meta['mechanic'] ?? 'Inspecting Mechanic') ?></b><br>Inspecting Mechanic</div>
    </td>
    <td align="right" valign="bottom" width="130">
      <barcode code="<?= esc($report['verifyUrl']) ?>" type="QR" class="barcode" size="0.9" error="M" />
      <div style="font-size:7pt;color:#6b7682;text-transform:uppercase;letter-spacing:1px">Scan to Verify</div>
    </td>
  </tr></table>
</div>

<h3 class="sec">Colour Rating Key</h3>
<table width="100%" cellspacing="0" style="font-size:8pt">
  <tr>
    <td style="border-left:4px solid <?= $rag('ok') ?>;padding:6px 8px"><b>Green · No Issues</b><br>No issues found</td>
    <td style="border-left:4px solid <?= $rag('minor') ?>;padding:6px 8px"><b>Blue · Minor</b><br>Not 100% — no repair needed</td>
    <td style="border-left:4px solid <?= $rag('lesser') ?>;padding:6px 8px"><b>Orange · Lesser</b><br>Approved if repaired</td>
    <td style="border-left:4px solid <?= $rag('major') ?>;padding:6px 8px"><b>Red · Major</b><br>Not fit to finance / purchase</td>
  </tr>
</table>

<p style="font-size:7.5pt;color:#6b7682;margin-top:14px;line-height:1.5">
  This inspection is purely visual and non-mechanical; no parts are removed. It is not a roadworthy certificate and should not be considered a recommendation to purchase. Checka by WheelsTurn makes no guarantee regarding latent defects not visually evident at the time of inspection.
</p>
