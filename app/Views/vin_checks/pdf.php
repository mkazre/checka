<?php
/** @var array $row VinReportModel row; @var array $data decoded payload; @var string $shareUrl */
$stolenFlag = false;
foreach ($data['stolenStatus'] ?? [] as $s) {
    if (stripos($s['status'], 'stolen') !== false && stripos($s['status'], 'no theft') === false && stripos($s['status'], 'no ') !== 0) {
        $stolenFlag = true;
    }
}
$badgeBg  = $stolenFlag ? '#fbeae8' : (! empty($data['accidentHistory']) ? '#fdf3e2' : '#e4f6ea');
$badgeFg  = $stolenFlag ? '#b42318' : (! empty($data['accidentHistory']) ? '#9a6513' : '#0f5f2e');
$timeline = service('vinCheck')->timeline($data);
?>
<style>
  body{font-family:sans-serif;font-size:9.5pt;color:#12171d}
  .band{background:#2d2d2d;color:#fff;padding:16px 18px}
  .band .vin{font-size:8pt;color:#aeb9c6;letter-spacing:1px}
  .band h2{font-size:18pt;margin:2px 0;color:#fff}
  .badge{display:inline-block;padding:6px 13px;border-radius:14px;font-weight:bold;font-size:10pt}
  h3.sec{font-size:10.5pt;margin:14px 0 5px;padding-bottom:3px;border-bottom:2px solid #37b34a}
  table.detail{width:100%;border-collapse:collapse;margin-top:4px}
  table.detail td{border:1px solid #d6dce1;padding:6px 8px;width:33%}
  table.detail .k{font-size:7pt;text-transform:uppercase;color:#6b7682}
  table.detail .v{font-weight:bold}
  .line{font-size:9.5pt;margin:3px 0}
  ul.list{margin:3px 0 0;padding-left:16px;font-size:9pt}
  .note{font-size:8pt;color:#6b7682;margin-top:5px}
  table.tl{width:100%;border-collapse:collapse;margin-top:4px}
  table.tl td{border-bottom:1px solid #eef1f3;padding:6px 4px;vertical-align:top}
  table.tl .tl-date{width:16%;font-size:8pt;color:#6b7682}
  table.tl .tl-title{font-weight:bold;font-size:9pt}
  table.tl .tl-detail{font-size:8.5pt;color:#3d4753;margin-top:2px}
  table.tl .tl-src{font-size:7.5pt;color:#6b7682;margin-top:2px}
  table.tl .tl-photos img{width:46px;height:46px;margin:2px 3px 0 0;border:1px solid #d6dce1}
  .vrr-block{margin:1px 0}
  .vrr-row{padding:2px 0 2px 8px;border-left:1.5px solid #d6dce1;margin:2px 0}
  .vrr-k{font-size:7pt;letter-spacing:.04em;text-transform:uppercase;color:#6b7682}
  .vrr-v{font-size:8.5pt}
  .vrr-nested{margin-left:10px}
  .vrr-empty{color:#6b7682;font-style:italic}
</style>

<table width="100%"><tr>
  <td width="60%">
    <?php $logoFile = FCPATH . 'assets/img/logo.png'; ?>
    <?php if (is_file($logoFile)): ?><img src="<?= $logoFile ?>" style="height:40px"><?php endif; ?>
  </td>
  <td width="40%" align="right"><barcode code="<?= esc($shareUrl) ?>" type="QR" class="barcode" size="0.7" error="M" /></td>
</tr></table>

<table width="100%" class="band" style="margin-top:8px"><tr>
  <td width="70%">
    <div class="vin">VIN</div>
    <h2><?= esc($row['vin']) ?></h2>
    <div class="vin"><?= esc(trim(($row['year'] ?: '') . ' ' . ($row['make'] ?: '') . ' ' . ($row['model'] ?: '')) ?: 'Vehicle specs not decoded') ?></div>
  </td>
  <td width="30%" align="right">
    <span class="badge" style="background:<?= $badgeBg ?>;color:<?= $badgeFg ?>"><?= esc($row['headline'] ?: 'No data returned') ?></span>
  </td>
</tr></table>

<?php if (! empty($data['stolenStatus']) || ! empty($data['titleStatus']) || ! empty($data['registryMatch'])): ?>
<h3 class="sec">Status Summary</h3>
<?php foreach ($data['stolenStatus'] as $s): ?>
  <p class="line"><b>Theft/title brand</b> — <?= esc($s['status']) ?> (<?= esc($s['provider']) ?>)</p>
<?php endforeach; ?>
<?php foreach ($data['titleStatus'] as $s): ?>
  <p class="line"><b>Title</b> — <?= esc($s['status']) ?> (<?= esc($s['provider']) ?>)</p>
<?php endforeach; ?>
<?php foreach ($data['registryMatch'] as $m): ?>
  <p class="line"><b>SA Registry (NATIS)</b> — <?= esc(is_array($m['data']) ? ($m['data']['status'] ?? json_encode($m['data'])) : (string) $m['data']) ?> (<?= esc($m['provider']) ?>)</p>
<?php endforeach; ?>
<?php if (! empty($data['registryMatch'])): ?>
  <p class="note">A registry match confirms this VIN is genuine and registered — it is not an accident or stolen-vehicle history check.</p>
<?php endif; ?>
<?php endif; ?>

<?php if ($timeline !== []): ?>
<h3 class="sec">Activity Timeline</h3>
<table class="tl">
  <?php foreach ($timeline as $t): ?>
  <tr>
    <td class="tl-date"><?= esc($t['date'] ? date('Y-m-d', strtotime($t['date'])) : 'Undated') ?></td>
    <td>
      <div class="tl-title"><?= esc($t['title']) ?></div>
      <?php if ($t['detail'] !== ''): ?><div class="tl-detail"><?= nl2br(esc($t['detail'])) ?></div><?php endif; ?>
      <div class="tl-src">Source: <?= esc($t['provider']) ?></div>
      <?php if (! empty($t['photos'])): ?>
        <div class="tl-photos">
          <?php foreach ($t['photos'] as $p): if (! is_file($p['localThumb'] ?? '')) continue; ?>
            <img src="<?= $p['localThumb'] ?>">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if (! empty($data['specs'])): ?>
<h3 class="sec">Vehicle Specifications</h3>
<table class="detail"><tr>
<?php $i = 0; foreach ($data['specs'] as $k => $v): ?>
  <td><div class="k"><?= esc(ucwords(str_replace('_', ' ', $k))) ?></div><div class="v"><?= esc((string) $v) ?></div></td>
  <?php $i++; if ($i % 3 === 0): ?></tr><tr><?php endif; ?>
<?php endforeach; ?>
</tr></table>
<?php endif; ?>

<h3 class="sec">Sources Checked</h3>
<p class="line"><?= esc($row['providers_used'] ?: '—') ?></p>

<?php foreach ((array) ($data['raw'] ?? []) as $providerLabel => $providerRaw): if (empty($providerRaw)) continue; ?>
<h3 class="sec">Full Data — <?= esc($providerLabel) ?></h3>
<?= vin_render_raw($providerRaw) ?>
<?php endforeach; ?>

<p class="note" style="margin-top:10px">Report generated <?= esc(date('Y-m-d H:i', strtotime($row['created_at']))) ?> by Checka+ by WheelsTurn. Verify online: <?= esc($shareUrl) ?></p>
