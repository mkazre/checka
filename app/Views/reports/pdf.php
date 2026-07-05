<?php
  $v = $report['vehicle'] ?? []; $ins = $report['inspection']; $meta = $report['meta'] ?? [];
  $vs = $compute['verdict']['status']; $counts = $compute['counts'];
  $rag = fn($s)=>CHECKA_RATINGS[$s]['colour'] ?? '#15803d';
  $bg  = fn($s)=>CHECKA_RATINGS[$s]['bg'] ?? '#e7f6ec';
  $label = fn($s)=>CHECKA_RATINGS[$s]['label'] ?? 'No Issues';
  $sigFile = $ins['signature_path'] ? $uploadDir . $ins['signature_path'] : null;
?>
<style>
  body{font-family:sans-serif;font-size:9.5pt;color:#12171d}
  h1,h2,h3{margin:0}
  .band{background:#151b22;color:#fff;padding:14px 16px}
  .band .yr{font-size:8pt;color:#8395a6;letter-spacing:2px;text-transform:uppercase}
  .band h2{font-size:20pt;margin:2px 0;color:#fff}
  .band .vin{font-size:8.5pt;color:#aeb9c6}
  .verdict{padding:6px 12px;border-radius:14px;font-weight:bold;font-size:10pt;display:inline-block}
  .metrics td{width:25%;text-align:center;border:1px solid #2b3742;background:#1e2731;color:#fff;padding:8px}
  .metrics .n{font-size:16pt;font-weight:bold}
  .metrics .t{font-size:7pt;color:#9aa7b4;text-transform:uppercase;letter-spacing:1px}
  h3.sec{font-size:11pt;margin:16px 0 6px;padding-bottom:3px;border-bottom:2px solid #12171d}
  table.checks{width:100%;border-collapse:collapse;margin-top:4px}
  table.checks th{font-size:7pt;text-transform:uppercase;letter-spacing:.5px;color:#6b7682;text-align:left;border-bottom:1px solid #d6dce1;padding:4px 6px}
  table.checks td{padding:5px 6px;border-bottom:1px solid #eef1f3;vertical-align:top;font-size:9pt}
  .ref{color:#6b7682;font-size:8.5pt}
  .chip{padding:2px 7px;border-radius:10px;font-size:8pt;font-weight:bold}
  .detail td{border:1px solid #d6dce1;padding:6px 8px;width:33%}
  .detail .k{font-size:7pt;text-transform:uppercase;color:#6b7682}
  .detail .v{font-weight:bold}
  .concl{background:#f7f9fa;border:1px solid #d6dce1;padding:12px}
</style>

<div class="band">
  <table width="100%"><tr>
    <td>
      <div class="yr"><?= esc(($v['year']??'').' · '.($v['colour']??'')) ?></div>
      <h2><?= esc(trim(($v['make']??'').' '.($v['model']??'')) ?: 'Vehicle') ?></h2>
      <div class="vin">VIN <?= esc($v['vin'] ?? '—') ?> &nbsp;·&nbsp; <?= esc($v['plate'] ?? '') ?></div>
    </td>
    <td align="right">
      <div style="font-size:7pt;color:#8395a6;text-transform:uppercase;letter-spacing:1px">Overall Verdict</div>
      <div class="verdict" style="background:<?= $rag($vs) ?>;color:#fff;margin-top:4px"><?= esc($compute['verdict']['text']) ?></div>
      <div style="font-size:20pt;font-weight:bold;color:<?= $rag($vs) ?>;margin-top:8px"><?= $compute['score'] ?><span style="font-size:9pt;color:#8395a6">/100</span></div>
    </td>
  </tr></table>
</div>

<table class="metrics" width="100%" cellspacing="0" style="margin-top:2px"><tr>
  <td><div class="n" style="color:#8fd3a7"><?= $counts['ok'] ?></div><div class="t">No Issues</div></td>
  <td><div class="n" style="color:#9db9d6"><?= $counts['minor'] ?></div><div class="t">Minor</div></td>
  <td><div class="n" style="color:#e9c48c"><?= $counts['lesser'] ?></div><div class="t">Lesser</div></td>
  <td><div class="n" style="color:#e9a29c"><?= $counts['major'] ?></div><div class="t">Major</div></td>
</tr></table>

<h3 class="sec">Vehicle Details</h3>
<table class="detail" width="100%" cellspacing="0">
  <tr>
    <td><div class="k">Inspection Date</div><div class="v"><?= esc($ins['inspection_date']) ?></div></td>
    <td><div class="k">Odometer</div><div class="v"><?= esc($meta['km_before'] ?? $v['odometer'] ?? '—') ?></div></td>
    <td><div class="k">Colour</div><div class="v"><?= esc($v['colour'] ?? '—') ?></div></td>
  </tr>
  <tr>
    <td><div class="k">Dealer / Seller</div><div class="v"><?= esc($meta['dealer'] ?? $v['seller'] ?? '—') ?></div></td>
    <td><div class="k">Mechanic</div><div class="v"><?= esc($meta['mechanic'] ?? '—') ?></div></td>
    <td><div class="k">Report ID</div><div class="v"><?= esc(substr($ins['report_uid'],0,8)) ?></div></td>
  </tr>
</table>

<?php if (! empty($compute['findings'])): ?>
<h3 class="sec">Summary of Findings</h3>
<table class="checks" cellspacing="0">
  <thead><tr><th width="8%">Ref</th><th width="42%">Item</th><th width="18%">Result</th><th>Comment</th></tr></thead>
  <tbody>
  <?php foreach ($compute['findings'] as $f): ?>
    <tr>
      <td class="ref"><?= esc($f['ref']) ?></td>
      <td><?= esc(preg_replace('/ Checks?$/','',$f['section_title'] ?? '')) ?> » <?= esc($f['name']) ?></td>
      <td><span class="chip" style="background:<?= $bg($f['status']) ?>;color:<?= $rag($f['status']) ?>"><?= esc($label($f['status'])) ?></span></td>
      <td><?= esc($f['comment']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

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
        <td><?= esc($item['comment'] ?: $item['text']) ?></td>
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
              <?php if (isset($row[$c])):
                $p   = $row[$c];
                $tp  = $p['thumb_path'] ?? (dirname($p['path']) . '/thumb_' . basename($p['path']));
                $abs = $uploadDir . $tp;
                if (! is_file($abs)) { $abs = $uploadDir . $p['path']; }
                if (is_file($abs)): ?>
                  <img src="<?= $abs ?>" style="width:125px;height:auto">
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
