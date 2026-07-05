<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Scan Licence Disc</h1><div class="sub">Point the camera at the disc — line up the barcode inside the red box.</div></div>
</div>

<div class="grid cols-2" style="align-items:start">
  <div class="card">
    <div class="card-h"><h3>1 · Live scan</h3></div>
    <div class="card-b">
      <div id="scanWrap" style="position:relative;background:#0b0e12;border-radius:12px;overflow:hidden;aspect-ratio:3/4;display:grid;place-items:center">
        <video id="video" playsinline muted autoplay style="width:100%;height:100%;object-fit:cover"></video>
        <div id="guide" style="position:absolute;left:6%;right:6%;top:37%;height:26%;border:3px solid #ef3d3d;border-radius:10px;box-shadow:0 0 0 3000px rgba(0,0,0,.42);pointer-events:none"></div>
        <div id="camHint" style="position:absolute;color:#9fb0bf;font-family:var(--mono);font-size:12.5px;text-align:center;padding:0 24px">Tap “Start camera” and allow camera access</div>
      </div>
      <div style="display:flex;gap:10px;margin-top:12px">
        <button type="button" class="btn" id="startBtn">Start camera</button>
        <button type="button" class="btn ghost" id="stopBtn" style="display:none">Stop</button>
      </div>
      <div id="status" class="mono" style="font-size:12.5px;margin-top:12px;color:var(--ink-3)">Camera off.</div>

      <div style="margin-top:14px;border-top:1px solid var(--line);padding-top:12px">
        <div class="sub" style="font-size:12px;color:var(--ink-3);margin-bottom:6px">No camera on this device? Upload a sharp, close photo of the barcode:</div>
        <label class="btn ghost sm" style="cursor:pointer">Upload disc photo
          <input id="discPhoto" type="file" accept="image/*" hidden>
        </label>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-h"><h3>2 · Confirm &amp; save</h3></div>
    <div class="card-b">
      <form method="post" action="<?= site_url('vehicles') ?>" id="discForm" class="grid cols-2" style="gap:12px">
        <?= csrf_field() ?>
        <input type="hidden" name="disc_raw" id="f_disc_raw">
        <label class="fld"><span>Make</span><input type="text" name="make" id="f_make"></label>
        <label class="fld"><span>Model</span><input type="text" name="model" id="f_model"></label>
        <label class="fld"><span>VIN</span><input type="text" name="vin" id="f_vin" class="mono"></label>
        <label class="fld"><span>Engine No.</span><input type="text" name="engine_number" id="f_engine_number" class="mono"></label>
        <label class="fld"><span>Registration (plate)</span><input type="text" name="plate" id="f_plate"></label>
        <label class="fld"><span>Licence No.</span><input type="text" name="licence_no" id="f_licence_no"></label>
        <label class="fld"><span>Colour</span><input type="text" name="colour" id="f_colour"></label>
        <label class="fld"><span>Body / Description</span><input type="text" name="body_type" id="f_body_type"></label>
        <label class="fld"><span>Licence Expiry</span><input type="text" name="licence_expiry" id="f_licence_expiry" placeholder="YYYY-MM-DD"></label>
        <label class="fld"><span>Date of Test</span><input type="text" name="date_of_test" id="f_date_of_test" placeholder="YYYY-MM-DD"></label>
        <div style="grid-column:span 2;margin-top:6px;display:flex;gap:10px">
          <button class="btn" type="submit">Save &amp; open profile →</button>
          <a class="btn ghost" href="<?= site_url('vehicles') ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js"></script>
<script>
const CSRF_HEADER = "<?= csrf_header() ?>";
const CSRF_HASH   = "<?= csrf_hash() ?>";
const PARSE_URL   = "<?= site_url('vehicles/parse-disc') ?>";
const statusEl = document.getElementById('status');
const video    = document.getElementById('video');
let stream = null, scanning = false, reader = null;
const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d', { willReadFrequently: true });

function setStatus(msg, color){ statusEl.textContent = msg; statusEl.style.color = color || 'var(--ink-3)'; }

function buildHints(){
  const hints = new Map();
  hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
    ZXing.BarcodeFormat.PDF_417, ZXing.BarcodeFormat.QR_CODE, ZXing.BarcodeFormat.DATA_MATRIX,
    ZXing.BarcodeFormat.AZTEC, ZXing.BarcodeFormat.CODE_128, ZXing.BarcodeFormat.CODE_39
  ]);
  hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
  return hints;
}

function getReader(){
  if (!reader){ reader = new ZXing.MultiFormatReader(); reader.setHints(buildHints()); }
  return reader;
}

function decodeCanvas(){
  try {
    const lum = new ZXing.HTMLCanvasElementLuminanceSource(canvas);
    const bmp = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(lum));
    const result = getReader().decode(bmp, buildHints());
    return result ? result.getText() : null;
  } catch(e){ return null; } finally { try { getReader().reset(); } catch(e){} }
}

function fill(fields){
  const keys = ['make','model','vin','engine_number','plate','licence_no','colour','body_type','licence_expiry','date_of_test','disc_raw'];
  let filled = 0;
  keys.forEach(k => { const el = document.getElementById('f_'+k); if (el && fields[k]) { el.value = fields[k]; if(k!=='disc_raw') filled++; } });
  setStatus(filled ? `Decoded ✓ — ${filled} fields filled. Review on the right and save.` : 'Barcode read but few fields matched — check the fields.', filled ? 'var(--ok)' : 'var(--lesser)');
}

async function sendRaw(raw){
  try {
    const res = await fetch(PARSE_URL, {
      method:'POST',
      headers:{ 'Content-Type':'application/x-www-form-urlencoded', [CSRF_HEADER]:CSRF_HASH },
      body: 'raw=' + encodeURIComponent(raw) + '&' + encodeURIComponent(CSRF_HEADER) + '=' + encodeURIComponent(CSRF_HASH)
    });
    const j = await res.json();
    if (j.ok) fill(j.fields); else setStatus(j.error || 'Could not parse', 'var(--major)');
  } catch(e){ setStatus('Server error while parsing', 'var(--major)'); }
}

function scanLoop(){
  if (!scanning) return;
  if (video.readyState >= 2 && video.videoWidth) {
    const gx = 0.06, gw = 0.88, gy = 0.37, gh = 0.26;
    const sx = Math.round(video.videoWidth * gx), sw = Math.round(video.videoWidth * gw);
    const sy = Math.round(video.videoHeight * gy), sh = Math.round(video.videoHeight * gh);
    canvas.width = sw; canvas.height = sh;
    ctx.drawImage(video, sx, sy, sw, sh, 0, 0, sw, sh);
    const text = decodeCanvas();
    if (text) { onDecoded(text); return; }
  }
  setTimeout(() => { if (scanning) requestAnimationFrame(scanLoop); }, 110);
}

async function startScan(){
  document.getElementById('camHint').style.display = 'none';
  setStatus('Requesting camera…');
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
      audio: false
    });
    video.srcObject = stream;
    await video.play();
    scanning = true;
    document.getElementById('startBtn').style.display = 'none';
    document.getElementById('stopBtn').style.display = '';
    setStatus('Scanning… fill the red box with the barcode and hold steady.');
    requestAnimationFrame(scanLoop);
  } catch(e){
    let m = e && e.name === 'NotAllowedError' ? 'Camera permission was blocked. Allow camera for this site and retry.'
          : e && e.name === 'NotFoundError' ? 'No camera found on this device.'
          : 'Camera error: ' + (e && e.message ? e.message : e);
    setStatus(m, 'var(--major)');
    document.getElementById('camHint').style.display = '';
  }
}

function stopScan(){
  scanning = false;
  if (stream){ stream.getTracks().forEach(t => t.stop()); stream = null; }
  video.srcObject = null;
  document.getElementById('startBtn').style.display = '';
  document.getElementById('stopBtn').style.display = 'none';
}

function onDecoded(text){
  setStatus('Barcode read ✓ — decoding fields…', 'var(--ok)');
  stopScan();
  sendRaw(text);
}

document.getElementById('startBtn').addEventListener('click', startScan);
document.getElementById('stopBtn').addEventListener('click', () => { stopScan(); setStatus('Stopped.'); });
window.addEventListener('beforeunload', stopScan);

document.getElementById('discPhoto').addEventListener('change', (e) => {
  const file = e.target.files[0]; if(!file) return;
  setStatus('Decoding photo…');
  const img = new Image();
  img.onload = () => {
    const maxW = 2000, scale = img.naturalWidth > maxW ? maxW / img.naturalWidth : 1;
    canvas.width = Math.round(img.naturalWidth * scale);
    canvas.height = Math.round(img.naturalHeight * scale);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    const text = decodeCanvas();
    if (text) onDecoded(text);
    else setStatus('Could not read the barcode in that photo — get closer so the barcode fills the frame, avoid glare, then retry.', 'var(--major)');
  };
  img.src = URL.createObjectURL(file);
});
</script>
<?= $this->endSection() ?>
