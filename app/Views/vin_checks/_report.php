<?php
/**
 * Shared VIN Check report body — included by view.php (logged-in), share.php
 * (public QR/email landing) and pdf.php (mPDF render) so the three never
 * drift from one another. Expects $row (vin_reports row) and $data
 * (decoded payload, see VinCheckService::run()). Set $showPhotos = false
 * before including on a page the recipient isn't logged into — photo URLs
 * require a Checka session.
 */
$stolenFlag = false;
foreach ($data['stolenStatus'] ?? [] as $s) {
    if (stripos($s['status'], 'stolen') !== false && stripos($s['status'], 'no theft') === false && stripos($s['status'], 'no ') !== 0) {
        $stolenFlag = true;
    }
}
$timeline = service('vinCheck')->timeline($data);
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

<?php if (! empty($data['stolenStatus']) || ! empty($data['titleStatus']) || ! empty($data['registryMatch'])): ?>
<div class="vcr-section vcr-summary">
  <h4>Status Summary</h4>
  <?php foreach ($data['stolenStatus'] as $s): ?>
    <p class="vcr-line"><b>Theft/title brand</b> — <?= esc($s['status']) ?> <span class="vcr-src">(<?= esc($s['provider']) ?>)</span></p>
  <?php endforeach; ?>
  <?php foreach ($data['titleStatus'] as $s): ?>
    <p class="vcr-line"><b>Title</b> — <?= esc($s['status']) ?> <span class="vcr-src">(<?= esc($s['provider']) ?>)</span></p>
  <?php endforeach; ?>
  <?php foreach ($data['registryMatch'] as $m): ?>
    <p class="vcr-line"><b>SA Registry (NATIS)</b> — <?= esc(is_array($m['data']) ? ($m['data']['status'] ?? json_encode($m['data'])) : (string) $m['data']) ?> <span class="vcr-src">(<?= esc($m['provider']) ?>)</span></p>
  <?php endforeach; ?>
  <?php if (! empty($data['registryMatch'])): ?>
    <p class="vcr-note">A registry match confirms this VIN is genuine and registered — it is not an accident or stolen-vehicle history check.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($timeline !== []): ?>
<div class="vcr-section">
  <h4>Activity Timeline</h4>
  <div class="vcr-timeline">
    <?php foreach ($timeline as $t): ?>
      <div class="vcr-tl-item vcr-tl-<?= esc($t['type']) ?>">
        <div class="vcr-tl-date"><?= esc($t['date'] ? date('Y-m-d', strtotime($t['date'])) : 'Undated') ?></div>
        <div class="vcr-tl-body">
          <div class="vcr-tl-title"><?= esc($t['title']) ?></div>
          <?php if ($t['detail'] !== ''): ?><div class="vcr-tl-detail"><?= nl2br(esc($t['detail'])) ?></div><?php endif; ?>
          <div class="vcr-tl-src">
            Source: <?= esc($t['provider']) ?>
            <?php if (! empty($t['reportUid'])): ?> · <a href="<?= site_url('reports/' . $t['reportUid']) ?>">View full inspection</a><?php endif; ?>
          </div>
          <?php if (! empty($t['photos']) && ($showPhotos ?? true)): ?>
            <div class="vcr-tl-photos">
              <?php foreach ($t['photos'] as $p): ?>
                <a href="<?= esc($p['url']) ?>" target="_blank" rel="noopener"><img src="<?= esc($p['thumb']) ?>" alt="<?= esc($p['caption']) ?>"></a>
              <?php endforeach; ?>
            </div>
          <?php elseif (! empty($t['photos'])): ?>
            <div class="vcr-tl-photos-note"><?= count($t['photos']) ?> photo<?= count($t['photos']) === 1 ? '' : 's' ?> on file — view in the app to see them.</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

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

<?php foreach ((array) ($data['raw'] ?? []) as $providerLabel => $providerRaw): if (empty($providerRaw)) continue; ?>
<div class="vcr-section">
  <details>
    <summary><h4 style="display:inline">Full data — <?= esc($providerLabel) ?></h4></summary>
    <div style="margin-top:10px"><?= vin_render_raw($providerRaw) ?></div>
  </details>
</div>
<?php endforeach; ?>
