<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verify Report · Checka by WheelsTurn</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  body{margin:0;background:#f4f5f6;font-family:'Inter',system-ui,sans-serif;color:#2d2d2d;display:grid;place-items:center;min-height:100vh;padding:20px}
  .card{background:#fff;border:1px solid #d6dce1;border-radius:16px;max-width:440px;width:100%;overflow:hidden;box-shadow:0 20px 50px rgba(16,23,29,.1)}
  .top{background:#fff;color:#2d2d2d;padding:20px 26px;border-bottom:3px solid #37b34a;text-align:center}
  .top img{height:56px;width:auto}
  .top small{display:block;margin-top:6px;font-family:'JetBrains Mono';font-size:9.5px;letter-spacing:.2em;color:#7a7a7a;text-transform:uppercase}
  .body{padding:26px}
  .badge{display:inline-flex;align-items:center;gap:9px;padding:8px 16px;border-radius:999px;font-weight:700;font-size:15px}
  .badge.ok{background:#e7f6ec;color:#15803d}
  .badge.no{background:#fbeae8;color:#b42318}
  .badge .dot{width:11px;height:11px;border-radius:50%;background:currentColor}
  h2{font-weight:800;letter-spacing:-.01em;margin:16px 0 4px}
  .row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #eef1f3;font-size:14px}
  .row b{font-family:'JetBrains Mono';font-size:12.5px}
  .k{color:#6b7682}
</style>
<link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/icon-32.png"><link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
</head>
<body>
<div class="card">
  <div class="top">
    <img src="<?= base_url('assets/img/logo.png') ?>" alt="CheckA+"><small>Report Verification</small>
  </div>
  <div class="body">
    <?php if ($valid): ?>
      <span class="badge ok"><span class="dot"></span>Genuine — Completed Report</span>
    <?php elseif ($inspection): ?>
      <span class="badge" style="background:#fbeee0;color:#b45309"><span class="dot"></span>Report exists — not yet finalised</span>
    <?php else: ?>
      <span class="badge no"><span class="dot"></span>No matching report found</span>
    <?php endif; ?>

    <?php if ($inspection): ?>
      <h2><?= esc(trim((($vehicle['year'] ?? '').' '.($vehicle['make'] ?? '').' '.($vehicle['model'] ?? ''))) ?: 'Vehicle') ?></h2>
      <div class="row"><span class="k">Report ID</span><b><?= esc(substr($uid,0,8)) ?></b></div>
      <div class="row"><span class="k">Registration</span><b><?= esc($vehicle['plate'] ?? '—') ?></b></div>
      <div class="row"><span class="k">VIN</span><b><?= esc($vehicle['vin'] ?? '—') ?></b></div>
      <div class="row"><span class="k">Inspection Date</span><b><?= esc($inspection['inspection_date'] ?? '—') ?></b></div>
      <?php if ($inspection['status']==='completed'): ?>
        <div class="row"><span class="k">Condition Score</span><b><?= esc($inspection['score']) ?>/100</b></div>
        <div class="row" style="border-bottom:none"><span class="k">Verdict</span><b><?= esc($inspection['verdict_text']) ?></b></div>
      <?php endif; ?>
    <?php else: ?>
      <p style="color:#6b7682">The verification code did not match any inspection in our records. Please check the code and try again.</p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
