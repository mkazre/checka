<?php
  use App\Services\QuoteService;
  $ins = $report['inspection'] ?? null;
  $v   = $report['vehicle'] ?? [];
  $signed = $ins && ! empty($ins['client_signed_at']);
  $rag = fn($s) => $ratings[$s]['colour'] ?? '#15803d';
  $bg  = fn($s) => $ratings[$s]['bg'] ?? '#e7f6ec';
  $lbl = fn($s) => $ratings[$s]['label'] ?? 'No Issues';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vehicle Report · Checka by WheelsTurn</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  body{margin:0;background:#f4f5f6;font-family:'Inter',system-ui,sans-serif;color:#2d2d2d;padding:20px}
  .wrap{max-width:640px;margin:0 auto}
  .card{background:#fff;border:1px solid #d6dce1;border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(16,23,29,.08);margin-bottom:16px}
  .top{padding:20px 24px;border-bottom:3px solid #37b34a;text-align:center}
  .top img{height:52px}
  .top small{display:block;margin-top:6px;font-family:'JetBrains Mono';font-size:9.5px;letter-spacing:.2em;color:#7a7a7a;text-transform:uppercase}
  .body{padding:22px 24px}
  h1{font-size:22px;font-weight:800;margin:0 0 2px}
  h3{font-size:15px;margin:0 0 10px}
  .sub{color:#6b7682;font-family:'JetBrains Mono';font-size:12px}
  .verdict{display:inline-block;padding:6px 14px;border-radius:8px;font-weight:700;font-size:13px;margin-top:10px;border-left:5px solid}
  .score{font-size:30px;font-weight:800}
  .row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eef1f3;font-size:14px}
  .row b{font-family:'JetBrains Mono';font-size:12.5px}
  .k{color:#6b7682}
  .flash{padding:12px 15px;border-radius:10px;margin-bottom:16px;font-weight:600;font-size:14px}
  .flash.ok{background:#e7f6ec;color:#0f5f2e;border:1px solid #b6e3c4}
  .flash.err{background:#fbeae8;color:#8a1a12;border:1px solid #f0c5bf}
  table.q{width:100%;border-collapse:collapse;margin-top:6px}
  table.q th{text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#6b7682;padding:8px 6px;border-bottom:1px solid #d6dce1}
  table.q td{padding:9px 6px;border-bottom:1px solid #eef1f3;font-size:13.5px;vertical-align:top}
  table.q .cost{text-align:right;font-family:'JetBrains Mono';white-space:nowrap;font-weight:700}
  .totals{margin-top:12px;font-size:14px}
  .totals .line{display:flex;justify-content:space-between;padding:5px 6px}
  .totals .grand{font-weight:800;font-size:17px;border-top:2px solid #2d2d2d;margin-top:4px;padding-top:8px}
  .chk{display:flex;align-items:center;gap:7px;font-size:12.5px;color:#15803d;font-weight:600}
  .fld{display:block;margin-top:12px}
  .fld span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#6b7682;margin-bottom:5px}
  input[type=text],textarea{width:100%;border:1px solid #d6dce1;border-radius:9px;padding:11px 12px;font-size:15px;font-family:inherit}
  canvas.sig{width:100%;height:150px;border:1px dashed #b4bdc6;border-radius:10px;background:#fafbfc;touch-action:none;margin-top:4px}
  .btn{border:none;border-radius:10px;padding:13px 18px;font-size:15px;font-weight:700;cursor:pointer}
  .btn.green{background:#37b34a;color:#fff}
  .btn.ghost{background:#fff;border:1px solid #d6dce1;color:#2d2d2d}
  .btnrow{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
  .statusbig{padding:14px 16px;border-radius:10px;font-weight:700;text-align:center}
  .statusbig.approved{background:#e7f6ec;color:#0f5f2e}
  .statusbig.declined{background:#fbeae8;color:#8a1a12}
</style>
</head>
<body>
<div class="wrap">

  <?php if (session()->getFlashdata('message')): ?><div class="flash ok"><?= esc(session()->getFlashdata('message')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?><div class="flash err"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

  <div class="card">
    <div class="top"><img src="<?= base_url('assets/img/logo.png') ?>" alt="CheckA+"><small>Vehicle Inspection Report</small></div>

    <?php if (! $valid): ?>
      <div class="body"><p style="color:#6b7682">This report link is not available. It may not be finalised yet, or the link is incorrect.</p></div>
    <?php else: $vs = $compute['verdict']['status']; ?>
      <div class="body">
        <h1><?= esc(trim(($v['year']??'').' '.($v['make']??'').' '.($v['model']??'')) ?: 'Vehicle') ?></h1>
        <div class="sub"><?= esc($v['plate'] ?? $v['vin'] ?? '') ?></div>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:6px">
          <div class="verdict" style="color:<?= $rag($vs) ?>;border-color:<?= $rag($vs) ?>;background:<?= $bg($vs) ?>"><?= esc($compute['verdict']['text']) ?></div>
          <div style="text-align:right"><span class="score" style="color:<?= $rag($vs) ?>"><?= $compute['score'] ?></span><span style="color:#6b7682">/100</span></div>
        </div>
        <div style="margin-top:14px">
          <div class="row"><span class="k">Inspection Date</span><b><?= esc($ins['inspection_date'] ?: '—') ?></b></div>
          <div class="row"><span class="k">Report ID</span><b><?= esc(substr($uid,0,8)) ?></b></div>
          <div class="row" style="border-bottom:none"><span class="k">Findings</span><b><?= $compute['counts']['major'] ?> major · <?= $compute['counts']['lesser'] ?> lesser · <?= $compute['counts']['minor'] ?> minor</b></div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($valid): ?>

    <!-- Repair estimate + approval -->
    <?php if ($quote && $quote['count'] > 0): ?>
    <div class="card"><div class="body">
      <h3>Recommended Repairs</h3>
      <p class="sub" style="font-size:12px">Tick the repairs you approve. Prices include VAT at <?= rtrim(rtrim(number_format($quote['vatRate'],1),'0'),'.') ?>%.</p>
    </div></div>
    <?php endif; ?>

    <?php if ($signed): ?>
      <div class="card"><div class="body">
        <div class="statusbig <?= esc($ins['client_status']) ?>">
          <?= $ins['client_status']==='approved' ? '✓ Repairs approved' : '✕ Repairs declined' ?>
        </div>
        <div class="row" style="margin-top:12px"><span class="k">Signed by</span><b><?= esc($ins['client_name']) ?></b></div>
        <div class="row"><span class="k">Date</span><b><?= esc($ins['client_signed_at']) ?></b></div>
        <?php if (! empty($ins['client_note'])): ?><div class="row" style="border-bottom:none"><span class="k">Note</span><b style="max-width:60%;text-align:right"><?= esc($ins['client_note']) ?></b></div><?php endif; ?>
        <?php if (! empty($ins['client_signature'])): ?><div style="text-align:center;margin-top:12px"><img src="<?= esc($ins['client_signature']) ?>" alt="signature" style="height:70px"></div><?php endif; ?>
      </div></div>
    <?php else: ?>
      <form method="post" action="<?= site_url('portal/'.$uid) ?>" id="pform">
        <?= csrf_field() ?>
        <input type="hidden" name="decision" id="decision" value="approved">
        <input type="hidden" name="signature" id="sigData">

        <?php if ($quote && $quote['count'] > 0): ?>
        <div class="card"><div class="body">
          <table class="q">
            <thead><tr><th style="width:34px"></th><th>Repair</th><th class="cost">Price</th></tr></thead>
            <tbody>
              <?php foreach ($quote['items'] as $it): ?>
                <tr>
                  <td>
                    <input type="hidden" name="approve[<?= $it['value_id'] ?>]" value="0">
                    <input type="checkbox" name="approve[<?= $it['value_id'] ?>]" value="1" <?= (int)$it['approved']===1?'checked':'' ?>>
                  </td>
                  <td><b><?= esc($it['name']) ?></b><?php if($it['comment']): ?><br><span class="sub" style="font-size:11.5px"><?= esc($it['comment']) ?></span><?php endif; ?></td>
                  <td class="cost"><?= QuoteService::money($it['cost']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="totals">
            <div class="line"><span class="k">Subtotal</span><span><?= QuoteService::money($quote['subtotal']) ?></span></div>
            <div class="line"><span class="k">VAT (<?= rtrim(rtrim(number_format($quote['vatRate'],1),'0'),'.') ?>%)</span><span><?= QuoteService::money($quote['vat']) ?></span></div>
            <div class="line grand"><span>Total if all approved</span><span><?= QuoteService::money($quote['total']) ?></span></div>
          </div>
        </div></div>
        <?php endif; ?>

        <div class="card"><div class="body">
          <h3>Sign to confirm</h3>
          <label class="fld"><span>Your full name</span><input type="text" name="client_name" required placeholder="e.g. John Dlamini"></label>
          <label class="fld"><span>Note (optional)</span><textarea name="client_note" rows="2" placeholder="Any comments…"></textarea></label>
          <label class="fld"><span>Signature</span></label>
          <canvas class="sig" id="sig"></canvas>
          <button type="button" class="btn ghost" id="sigClear" style="margin-top:8px;padding:8px 14px;font-size:13px">Clear signature</button>

          <div class="btnrow">
            <button type="submit" class="btn green" style="flex:1" onclick="document.getElementById('decision').value='approved'">Approve &amp; Sign</button>
            <button type="submit" class="btn ghost" onclick="document.getElementById('decision').value='declined'">Decline repairs</button>
          </div>
          <p class="sub" style="font-size:11px;margin-top:12px">By signing you confirm you have reviewed this report. This is not a roadworthy certificate.</p>
        </div></div>
      </form>
    <?php endif; ?>

  <?php endif; ?>

  <div style="text-align:center;color:#9aa7b4;font-size:11px;padding:6px 0 20px">Powered by CheckA+ by WheelsTurn</div>
</div>

<script>
(function(){
  const c=document.getElementById('sig'); if(!c) return;
  const ctx=c.getContext('2d');
  function size(){ const r=c.getBoundingClientRect(); c.width=r.width; c.height=r.height; ctx.strokeStyle='#12171d'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.lineJoin='round'; }
  setTimeout(size,60); window.addEventListener('resize',size);
  let drawing=false;
  const pos=e=>{const r=c.getBoundingClientRect();const p=e.touches?e.touches[0]:e;return [p.clientX-r.left,p.clientY-r.top];};
  const start=e=>{drawing=true;const[x,y]=pos(e);ctx.beginPath();ctx.moveTo(x,y);};
  const move=e=>{if(!drawing)return;e.preventDefault();const[x,y]=pos(e);ctx.lineTo(x,y);ctx.stroke();document.getElementById('sigData').value=c.toDataURL('image/png');};
  const end=()=>{drawing=false;};
  c.addEventListener('mousedown',start);c.addEventListener('mousemove',move);window.addEventListener('mouseup',end);
  c.addEventListener('touchstart',start);c.addEventListener('touchmove',move);c.addEventListener('touchend',end);
  document.getElementById('sigClear').addEventListener('click',()=>{ctx.clearRect(0,0,c.width,c.height);document.getElementById('sigData').value='';});
})();
</script>
</body>
</html>
