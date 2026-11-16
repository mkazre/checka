<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Scan Licence Disc</h1><div class="sub">Point the camera at the disc's barcode, about a hand's length away. It reads automatically.</div></div>
</div>

<div class="grid cols-2" style="align-items:start">
  <div class="card">
    <div class="card-h"><h3>1 · Live scan</h3></div>
    <div class="card-b">
      <div id="scanWrap" style="position:relative;background:#0b0e12;border-radius:12px;overflow:hidden;aspect-ratio:3/4;display:grid;place-items:center">
        <video id="video" playsinline muted autoplay style="width:100%;height:100%;object-fit:cover"></video>
        <div id="guide" style="position:absolute;left:5%;right:5%;top:28%;height:44%;border:3px solid #ef3d3d;border-radius:12px;box-shadow:0 0 0 3000px rgba(0,0,0,.30);pointer-events:none;transition:border-color .15s"></div>
        <div id="camHint" style="position:absolute;color:#9fb0bf;font-family:var(--mono);font-size:12.5px;text-align:center;padding:0 24px">Tap “Start camera” and allow camera access</div>
      </div>
      <div style="display:flex;gap:10px;margin-top:12px">
        <button type="button" class="btn" id="startBtn">Start camera</button>
        <button type="button" class="btn ghost" id="stopBtn" style="display:none">Stop</button>
        <button type="button" class="btn ghost" id="torchBtn" style="display:none">Torch</button>
        <button type="button" class="btn ghost" id="zoomBtn" style="display:none">Zoom 2×</button>
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
<script src="https://cdn.jsdelivr.net/npm/barcode-detector@3.2.3/dist/iife/ponyfill.js"></script>
<script>
const CSRF_HEADER = "<?= csrf_header() ?>";
const CSRF_HASH   = "<?= csrf_hash() ?>";
const PARSE_URL   = "<?= site_url('vehicles/parse-disc') ?>";
const statusEl = document.getElementById('status');
const video    = document.getElementById('video');
const guide    = document.getElementById('guide');
const FORMATS  = ['pdf417', 'qr_code', 'data_matrix', 'aztec', 'code_128', 'code_39'];
let stream = null, track = null, scanning = false, detector = null, native = false, busy = false, torchOn = false, zoomed = false;
const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d', { willReadFrequently: true });

function setStatus(msg, color){ statusEl.textContent = msg; statusEl.style.color = color || 'var(--ink-3)'; }

/* Fastest reader available: the phone's own (Android Chrome — the same engine as the camera's QR reader),
   otherwise ZXing compiled to WebAssembly. Created once and warmed up before the camera opens. */
async function getDetector(){
  if (detector) return detector;
  try {
    if ('BarcodeDetector' in window) {
      const supported = await window.BarcodeDetector.getSupportedFormats();
      if (supported.includes('pdf417')) {
        detector = new window.BarcodeDetector({ formats: FORMATS.filter(f => supported.includes(f)) });
        native = true;
        return detector;
      }
    }
  } catch (e) {}
  detector = new BarcodeDetectionAPI.BarcodeDetector({ formats: FORMATS });
  native = false;
  return detector;
}
getDetector().then(d => { const c = document.createElement('canvas'); c.width = c.height = 8; d.detect(c).catch(() => {}); });

function fill(fields){
  const keys = ['make','model','vin','engine_number','plate','licence_no','colour','body_type','licence_expiry','date_of_test','disc_raw'];
  let filled = 0;
  keys.forEach(k => { const el = document.getElementById('f_'+k); if (el && fields[k]) { el.value = fields[k]; if(k!=='disc_raw') filled++; } });
  setStatus(filled ? `Decoded ✓ — ${filled} fields filled. Review and save.` : 'Barcode read but few fields matched — check the fields.', filled ? 'var(--ok)' : 'var(--lesser)');
  if (filled && window.innerWidth < 900) document.getElementById('discForm').scrollIntoView({ behavior: 'smooth' });
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

/* Native reader: the whole live frame. WebAssembly reader: the generous centre of the frame
   (where the guide is), scaled down a little so every frame stays quick. */
function frameSource(){
  if (native) return video;
  const vw = video.videoWidth, vh = video.videoHeight;
  const sx = Math.round(vw * 0.03), sw = Math.round(vw * 0.94);
  const sy = Math.round(vh * 0.22), sh = Math.round(vh * 0.56);
  const scale = Math.min(1, 1400 / sw);
  canvas.width = Math.round(sw * scale); canvas.height = Math.round(sh * scale);
  ctx.drawImage(video, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
  return canvas;
}

function pick(results){
  if (!results || !results.length) return null;
  const best = results.find(r => r.format === 'pdf417') || results[0];
  return best.rawValue || null;
}

function nextFrame(fn){
  if ('requestVideoFrameCallback' in video) video.requestVideoFrameCallback(() => fn());
  else setTimeout(fn, 40);
}

async function scanLoop(){
  if (!scanning) return;
  if (!busy && video.readyState >= 2 && video.videoWidth) {
    busy = true;
    try {
      const text = pick(await detector.detect(frameSource()));
      if (text) { busy = false; onDecoded(text); return; }
    } catch (e) {}
    busy = false;
  }
  nextFrame(scanLoop);
}

async function tuneCamera(){
  if (!track || !track.getCapabilities) return;
  const caps = track.getCapabilities();
  const adv = {};
  if (caps.focusMode && caps.focusMode.includes('continuous')) adv.focusMode = 'continuous';
  if (caps.exposureMode && caps.exposureMode.includes('continuous')) adv.exposureMode = 'continuous';
  if (Object.keys(adv).length) { try { await track.applyConstraints({ advanced: [adv] }); } catch (e) {} }
  document.getElementById('torchBtn').style.display = caps.torch ? '' : 'none';
  document.getElementById('zoomBtn').style.display = caps.zoom && caps.zoom.max >= 1.8 ? '' : 'none';
}

async function startScan(){
  document.getElementById('camHint').style.display = 'none';
  setStatus('Starting camera…');
  try {
    await getDetector();
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 }, frameRate: { ideal: 30 } },
      audio: false
    });
    track = stream.getVideoTracks()[0];
    video.srcObject = stream;
    await video.play();
    await tuneCamera();
    scanning = true; torchOn = false; zoomed = false;
    document.getElementById('zoomBtn').textContent = 'Zoom 2×';
    document.getElementById('startBtn').style.display = 'none';
    document.getElementById('stopBtn').style.display = '';
    setStatus('Scanning… point at the barcode and hold still for a moment.');
    scanLoop();
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
  if (stream){ stream.getTracks().forEach(t => t.stop()); stream = null; track = null; }
  video.srcObject = null;
  document.getElementById('startBtn').style.display = '';
  ['stopBtn', 'torchBtn', 'zoomBtn'].forEach(id => document.getElementById(id).style.display = 'none');
}

function onDecoded(text){
  guide.style.borderColor = '#37b34a';
  if (navigator.vibrate) navigator.vibrate(60);
  setStatus('Barcode read ✓ — decoding fields…', 'var(--ok)');
  stopScan();
  setTimeout(() => { guide.style.borderColor = '#ef3d3d'; }, 1200);
  sendRaw(text);
}

document.getElementById('startBtn').addEventListener('click', startScan);
document.getElementById('stopBtn').addEventListener('click', () => { stopScan(); setStatus('Stopped.'); });
document.getElementById('torchBtn').addEventListener('click', async () => {
  torchOn = !torchOn;
  try { await track.applyConstraints({ advanced: [{ torch: torchOn }] }); } catch (e) {}
});
document.getElementById('zoomBtn').addEventListener('click', async (e) => {
  zoomed = !zoomed;
  const caps = track.getCapabilities();
  try { await track.applyConstraints({ advanced: [{ zoom: zoomed ? Math.min(2, caps.zoom.max) : (caps.zoom.min || 1) }] }); } catch (err) {}
  e.target.textContent = zoomed ? 'Zoom 1×' : 'Zoom 2×';
});
window.addEventListener('beforeunload', stopScan);
document.addEventListener('visibilitychange', () => { if (document.hidden && scanning) { stopScan(); setStatus('Paused — tap Start camera to continue.'); } });

document.getElementById('discPhoto').addEventListener('change', async (e) => {
  const file = e.target.files[0]; if(!file) return;
  setStatus('Reading photo…');
  try {
    const d = await getDetector();
    const bmp = await createImageBitmap(file);
    let text = pick(await d.detect(bmp));
    if (!text && native) { text = pick(await new BarcodeDetectionAPI.BarcodeDetector({ formats: FORMATS }).detect(bmp)); }
    if (text) onDecoded(text);
    else setStatus('Could not read the barcode in that photo — get closer so the barcode fills most of the picture, avoid glare, then retry.', 'var(--major)');
  } catch (err) {
    setStatus('Could not open that photo. Try a JPG or PNG.', 'var(--major)');
  }
});
</script>
<?= $this->endSection() ?>
