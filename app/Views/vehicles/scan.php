<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Scan Licence Disc</h1><div class="sub">Photograph the disc — the barcode fills the vehicle profile automatically.</div></div>
</div>

<div class="grid cols-2" style="align-items:start">
  <div class="card">
    <div class="card-h"><h3>1 · Capture the disc</h3></div>
    <div class="card-b">
      <p class="sub" style="font-size:13px;color:var(--ink-3);margin-top:0">
        Fill the frame with the disc, focus on the <b>square barcode</b> in the middle, avoid glare. Then choose the photo below.
      </p>
      <label class="btn" style="cursor:pointer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/></svg>
        Take / choose disc photo
        <input id="discPhoto" type="file" accept="image/*" capture="environment" hidden>
      </label>
      <div id="status" class="mono" style="font-size:12.5px;margin-top:14px;color:var(--ink-3)">Waiting for a photo…</div>
      <img id="preview" style="max-width:100%;margin-top:12px;border-radius:10px;border:1px solid var(--line);display:none">
      <details style="margin-top:14px">
        <summary class="mono" style="font-size:12px;color:var(--ink-3);cursor:pointer">Barcode didn't scan? Paste decoded data manually</summary>
        <textarea id="rawManual" rows="3" placeholder="Paste raw disc barcode text here" style="margin-top:8px"></textarea>
        <button type="button" class="btn ghost sm" id="parseManual" style="margin-top:8px">Parse text</button>
      </details>
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

function setStatus(msg, color){ statusEl.textContent = msg; statusEl.style.color = color || 'var(--ink-3)'; }

function fill(fields){
  const map = ['make','model','vin','engine_number','plate','licence_no','colour','body_type','licence_expiry','date_of_test','disc_raw'];
  let filled = 0;
  map.forEach(k => { const el = document.getElementById('f_'+k); if (el && fields[k]) { el.value = fields[k]; if(k!=='disc_raw') filled++; } });
  setStatus(filled ? `Decoded ✓ — ${filled} fields filled. Review and save.` : 'Barcode read, but few fields matched — check/edit below.', filled ? 'var(--ok)' : 'var(--lesser)');
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

// Decode PDF417 from the chosen photo using ZXing core (version-stable API).
function decodeImage(img){
  setStatus('Decoding barcode…');
  try {
    const maxW = 1800;
    const scale = img.naturalWidth > maxW ? maxW / img.naturalWidth : 1;
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(img.naturalWidth * scale);
    canvas.height = Math.round(img.naturalHeight * scale);
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);

    const hints = new Map();
    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.PDF_417]);
    hints.set(ZXing.DecodeHintType.TRY_HARDER, true);

    const reader = new ZXing.MultiFormatReader();
    reader.setHints(hints);
    const luminance = new ZXing.HTMLCanvasElementLuminanceSource(canvas);
    const bitmap = new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(luminance));
    const result = reader.decode(bitmap, hints);
    sendRaw(result.getText());
  } catch(err){
    setStatus('Could not read the barcode — retake closer to the barcode, or paste the data manually below.', 'var(--major)');
  }
}

document.getElementById('discPhoto').addEventListener('change', (e) => {
  const file = e.target.files[0]; if(!file) return;
  const img = document.getElementById('preview');
  img.style.display = 'block';
  img.onload = () => decodeImage(img);
  img.src = URL.createObjectURL(file);
});

document.getElementById('parseManual').addEventListener('click', () => {
  const raw = document.getElementById('rawManual').value.trim();
  if (raw) sendRaw(raw);
});
</script>
<?= $this->endSection() ?>
