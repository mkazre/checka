<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<style>
  .sheet{max-width:820px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden;box-shadow:0 12px 40px rgba(16,23,29,.07)}
  .vcr-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:22px 26px;background:var(--panel);color:#e9eef2}
  .vcr-vin{font-family:var(--mono);font-size:13px;letter-spacing:.1em;color:#aeb9c6}
  .vcr-vehicle{font-size:22px;font-weight:600;margin-top:4px}
  .vcr-badge{padding:8px 14px;border-radius:999px;font-weight:600;font-size:13px;white-space:nowrap}
  .vcr-badge.ok{background:#e4f6ea;color:#0f5f2e}
  .vcr-badge.warn{background:#fdf3e2;color:#9a6513}
  .vcr-badge.bad{background:#fbeae8;color:#b42318}
  .vcr-section{padding:18px 26px;border-top:1px solid var(--line)}
  .vcr-section h4{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);margin-bottom:10px}
  .vcr-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--line);border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .vcr-cell{background:#fff;padding:10px 12px}
  .vcr-cell .k{display:block;font-family:var(--mono);font-size:9.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3)}
  .vcr-cell .v{display:block;font-weight:600;margin-top:2px}
  .vcr-line{font-size:13.5px;margin:4px 0}
  .vcr-list{margin:4px 0 0;padding-left:18px;font-size:13px}
  .vcr-note{font-size:11.5px;color:var(--ink-3);margin-top:8px}
  details > summary{cursor:pointer;list-style:none}
  details > summary::-webkit-details-marker{display:none}
  details > summary::before{content:'▸ ';color:var(--ink-3)}
  details[open] > summary::before{content:'▾ '}
  .vrr-block{margin:2px 0}
  .vrr-row{padding:4px 0 4px 10px;border-left:2px solid var(--line);margin:3px 0}
  .vrr-k{font-family:var(--mono);font-size:9.5px;letter-spacing:.04em;text-transform:uppercase;color:var(--ink-3)}
  .vrr-v{font-size:12.5px;margin-top:1px;word-break:break-word}
  .vrr-nested{margin-left:12px}
  .vrr-empty{color:var(--ink-3);font-style:italic}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>VIN Check</h1><div class="sub">Run <?= esc(date('Y-m-d H:i', strtotime($row['created_at']))) ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('vin-checks') ?>">Back to Archive</a>
  <a class="btn" href="<?= site_url('vin-checks/'.$row['report_uid'].'/pdf') ?>">Download PDF</a>
</div>

<div class="sheet"><?= $this->include('vin_checks/_report') ?></div>

<div class="card" style="max-width:820px;margin:16px auto 0">
  <div class="card-h"><h3>Share</h3></div>
  <div class="card-b grid" style="gap:14px">
    <div class="grid cols-2" style="gap:14px;align-items:end">
      <label class="fld"><span>Email to</span><input type="email" id="sendEmail" placeholder="client@example.com"></label>
      <div><button class="btn" id="sendBtn" type="button">Send PDF + QR by Email</button></div>
    </div>
    <p class="sub" id="sendMsg" style="font-size:12.5px;margin:0"></p>

    <div style="border-top:1px solid var(--line);padding-top:14px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div id="qr" style="width:120px;height:120px"></div>
      <div>
        <p class="sub" style="font-size:12.5px;margin:0 0 4px">Public share link (QR points here too):</p>
        <input type="text" readonly value="<?= esc($shareUrl) ?>" class="mono" style="width:340px;max-width:100%;padding:7px 10px;border:1px solid var(--line);border-radius:8px;font-size:11.5px" onclick="this.select()">
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  try { new QRCode(document.getElementById("qr"), {text:"<?= esc($shareUrl, 'js') ?>", width:120, height:120, colorDark:"#12171d", colorLight:"#fff"}); } catch(e){}

  (function(){
    const btn = document.getElementById('sendBtn');
    const CSRF_HEADER = "<?= csrf_header() ?>", CSRF_HASH = "<?= csrf_hash() ?>";
    const URL = "<?= site_url('vin-checks/'.$row['report_uid'].'/send') ?>";
    const msg = document.getElementById('sendMsg');
    btn.addEventListener('click', async () => {
      const email = document.getElementById('sendEmail').value || '';
      if(!email){ msg.style.color='#b42318'; msg.textContent='Enter an email address.'; return; }
      btn.disabled=true; const orig=btn.textContent; btn.textContent='Sending…'; msg.textContent='';
      try{
        const body = new URLSearchParams({ [CSRF_HEADER]:CSRF_HASH, email });
        const res = await fetch(URL, { method:'POST', headers:{ [CSRF_HEADER]:CSRF_HASH }, body });
        const j = await res.json();
        if(j.ok){ msg.style.color='#0f5f2e'; msg.textContent='Sent ✓'; }
        else { msg.style.color='#b42318'; msg.textContent=j.error||'Could not send.'; }
      }catch(e){ msg.style.color='#b42318'; msg.textContent='Request failed.'; }
      btn.disabled=false; btn.textContent=orig;
    });
  })();
</script>
<?= $this->endSection() ?>
