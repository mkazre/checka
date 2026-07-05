<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verify Report · Checka by WheelsTurn</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  body{margin:0;background:#eef1f3;font-family:'Inter',system-ui,sans-serif;color:#12171d;display:grid;place-items:center;min-height:100vh;padding:20px}
  .card{background:#fff;border:1px solid #d6dce1;border-radius:16px;max-width:440px;width:100%;overflow:hidden;box-shadow:0 20px 50px rgba(16,23,29,.1)}
  .top{background:#151b22;color:#fff;padding:22px 26px}
  .top .brand{font-family:'Space Grotesk';font-weight:700;letter-spacing:.08em}
  .top small{font-family:'JetBrains Mono';font-size:9.5px;letter-spacing:.2em;color:#8395a6;text-transform:uppercase}
  .body{padding:26px}
  .badge{display:inline-flex;align-items:center;gap:9px;padding:8px 16px;border-radius:999px;font-family:'Space Grotesk';font-weight:600;font-size:15px}
  .badge.ok{background:#e7f6ec;color:#15803d}
  .badge.no{background:#fbeae8;color:#b42318}
  .badge .dot{width:11px;height:11px;border-radius:50%;background:currentColor}
  h2{font-family:'Space Grotesk';margin:16px 0 4px}
  .row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #eef1f3;font-size:14px}
  .row b{font-family:'JetBrains Mono';font-size:12.5px}
  .k{color:#6b7682}
</style>
</head>
<body>
<div class="card">
  <div class="top">
    <div class="brand">Checka</div><small>by WheelsTurn · Report Verification</small>
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
