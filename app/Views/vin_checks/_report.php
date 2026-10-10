<?php
/**
 * Shared VIN Check report body — included by view.php (logged-in), share.php
 * (public QR/email landing) and pdf.php (mPDF render) so the three never
 * drift from one another. Expects $row (vin_reports row) and $data
 * (decoded payload, see VinCheckService::run()).
 */
$stolenFlag = false;
foreach ($data['stolenStatus'] ?? [] as $s) {
    if (stripos($s['status'], 'stolen') !== false && stripos($s['status'], 'no theft') === false && stripos($s['status'], 'no ') !== 0) {
        $stolenFlag = true;
    }
}
?>
<div class="vcr-head">
  <div>
    <div class="vcr-vin"><?= esc($row['vin']) ?></div>
    <div class="vcr-vehicle"><?= esc(trim(($row['year'] ?: '') . ' ' . ($row['make'] ?: '') . ' ' . ($row['model'] ?: '')) ?: 'Vehicle specs not decoded') ?></div>
  </div>
  <div class="vcr-badge <?= $stolenFlag ? 'bad' : (!empty($data['accidentHistory']) ? 'warn' : 'ok') ?>">
    <?= esc($row['headline'] ?: 'No data returned') ?>
  </div>
</div>

<?php if (! empty($data['specs'])): ?>
<div class="vcr-section">
  <h4>Vehicle Specifications</h4>
  <div class="vcr-grid">
    <?php foreach ($data['specs'] as $k => $v): ?>
      <div class="vcr-cell"><span class="k"><?= esc(ucwords(str_replace('_', ' ', $k))) ?></span><span class="v"><?= esc((string) $v) ?></span></div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if (! empty($data['stolenStatus'])): ?>
<div class="vcr-section">
  <h4>Stolen / Theft Status</h4>
  <?php foreach ($data['stolenStatus'] as $s): ?>
    <p class="vcr-line"><b><?= esc($s['provider']) ?>:</b> <?= esc($s['status']) ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (! empty($data['accidentHistory'])): ?>
<div class="vcr-section">
  <h4>Accident History</h4>
  <?php foreach ($data['accidentHistory'] as $group): ?>
    <p class="vcr-line" style="margin-bottom:4px"><b><?= esc($group['provider']) ?></b></p>
    <ul class="vcr-list">
      <?php foreach ((array) $group['events'] as $ev): ?>
        <li><?= esc(is_array($ev) ? (($ev['date'] ?? '') . ' — ' . ($ev['description'] ?? $ev['type'] ?? json_encode($ev))) : (string) $ev) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (! empty($data['titleStatus'])): ?>
<div class="vcr-section">
  <h4>Title Status</h4>
  <?php foreach ($data['titleStatus'] as $s): ?>
    <p class="vcr-line"><b><?= esc($s['provider']) ?>:</b> <?= esc($s['status']) ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (! empty($data['registryMatch'])): ?>
<div class="vcr-section">
  <h4>Registry Match (South Africa — NATIS)</h4>
  <?php foreach ($data['registryMatch'] as $m): ?>
    <p class="vcr-line"><b><?= esc($m['provider']) ?>:</b> <?= esc(is_array($m['data']) ? ($m['data']['status'] ?? json_encode($m['data'])) : (string) $m['data']) ?></p>
  <?php endforeach; ?>
  <p class="vcr-note">A registry match confirms this VIN is genuine and registered — it is not an accident or stolen-vehicle history check.</p>
</div>
<?php endif; ?>

<?php if (! empty($row['providers_used'])): ?>
<div class="vcr-section">
  <h4>Sources checked</h4>
  <p class="vcr-line"><?= esc($row['providers_used']) ?></p>
</div>
<?php endif; ?>

<?php $failed = json_decode((string) ($row['providers_failed'] ?? '[]'), true) ?: []; if (! empty($failed)): ?>
<div class="vcr-section">
  <h4>Not available</h4>
  <?php foreach ($failed as $f): ?>
    <p class="vcr-line sub"><?= esc($f['provider']) ?>: <?= esc($f['error']) ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>
