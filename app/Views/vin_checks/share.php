<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>VIN Check — <?= esc($row['vin']) ?> · Checka by WheelsTurn</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  :root{--line:#e3e7ea;--ink-2:#4b5563;--ink-3:#8a95a1;--panel:#2d2d2d;--mono:'JetBrains Mono',monospace}
  body{margin:0;background:#f4f5f6;font-family:'Inter',system-ui,sans-serif;color:#2d2d2d;display:grid;place-items:center;min-height:100vh;padding:20px}
  .top{text-align:center;margin-bottom:18px}
  .top img{height:50px}
  .sheet{max-width:620px;width:100%;background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;box-shadow:0 20px 50px rgba(16,23,29,.1)}
  .vcr-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:20px 24px;background:var(--panel);color:#e9eef2}
  .vcr-vin{font-family:var(--mono);font-size:13px;letter-spacing:.1em;color:#aeb9c6}
  .vcr-vehicle{font-size:20px;font-weight:600;margin-top:4px}
  .vcr-badge{padding:8px 14px;border-radius:999px;font-weight:700;font-size:12.5px;white-space:nowrap}
  .vcr-badge.ok{background:#e4f6ea;color:#0f5f2e}
  .vcr-badge.warn{background:#fdf3e2;color:#9a6513}
  .vcr-badge.bad{background:#fbeae8;color:#b42318}
  .vcr-section{padding:16px 24px;border-top:1px solid var(--line)}
  .vcr-section h4{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);margin:0 0 10px}
  .vcr-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1px;background:var(--line);border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .vcr-cell{background:#fff;padding:9px 11px}
  .vcr-cell .k{display:block;font-family:var(--mono);font-size:9px;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3)}
  .vcr-cell .v{display:block;font-weight:600;margin-top:2px;font-size:13.5px}
  .vcr-line{font-size:13px;margin:4px 0}
  .vcr-list{margin:4px 0 0;padding-left:18px;font-size:12.5px}
  .vcr-note{font-size:11px;color:var(--ink-3);margin-top:8px}
  .vcr-src{color:var(--ink-3);font-size:11px;font-weight:normal}
  .vcr-summary .vcr-line{margin:6px 0}
  .vcr-timeline{display:flex;flex-direction:column;gap:2px}
  .vcr-tl-item{display:flex;gap:12px;padding:10px 0;border-top:1px solid var(--line)}
  .vcr-tl-item:first-child{border-top:none;padding-top:2px}
  .vcr-tl-date{width:80px;flex:none;font-family:var(--mono);font-size:11px;color:var(--ink-3);padding-top:2px}
  .vcr-tl-body{flex:1;border-left:3px solid var(--line);padding-left:12px}
  .vcr-tl-accident .vcr-tl-body{border-left-color:#b42318}
  .vcr-tl-service .vcr-tl-body{border-left-color:#2f6fb0}
  .vcr-tl-title{font-weight:600;font-size:13px}
  .vcr-tl-detail{font-size:12px;color:var(--ink-2);margin-top:4px;white-space:pre-line}
  .vcr-tl-src{font-size:10.5px;color:var(--ink-3);margin-top:6px}
  .vcr-tl-photos-note{font-size:10.5px;color:var(--ink-3);margin-top:6px;font-style:italic}
  .foot{text-align:center;color:#aaa;font-size:11px;padding:14px}
  details > summary{cursor:pointer;list-style:none}
  details > summary::-webkit-details-marker{display:none}
  details > summary::before{content:'▸ ';color:var(--ink-3)}
  details[open] > summary::before{content:'▾ '}
  .vrr-block{margin:2px 0}
  .vrr-row{padding:4px 0 4px 10px;border-left:2px solid var(--line);margin:3px 0}
  .vrr-k{font-family:var(--mono);font-size:9px;letter-spacing:.04em;text-transform:uppercase;color:var(--ink-3)}
  .vrr-v{font-size:12px;margin-top:1px;word-break:break-word}
  .vrr-nested{margin-left:12px}
  .vrr-empty{color:var(--ink-3);font-style:italic}
</style>
</head>
<body>
<div style="width:100%;max-width:620px">
  <div class="top"><img src="<?= base_url('assets/img/logo.png') ?>" alt="CheckA+"></div>
  <div class="sheet"><?= $this->include('vin_checks/_report') ?></div>
  <div class="foot">Powered by CheckA+ by WheelsTurn</div>
</div>
</body>
</html>
