<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<style>
  .capbar{background:var(--panel);color:#e9eef2;border-radius:14px;padding:16px 20px;margin-bottom:14px;display:flex;align-items:center;gap:22px;flex-wrap:wrap}
  .capbar .vid .yr{font-family:var(--mono);font-size:11px;letter-spacing:.2em;color:#8395a6;text-transform:uppercase}
  .capbar .vid h2{font-size:20px;font-weight:600;margin-top:2px}
  .capbar .metric{text-align:center}
  .capbar .metric .n{font-family:var(--display);font-weight:700;font-size:26px;line-height:1}
  .capbar .metric .l{font-family:var(--mono);font-size:9px;letter-spacing:.14em;color:#8395a6;text-transform:uppercase;margin-top:3px}
  .wizbar{background:var(--paper);border:1px solid var(--line);border-radius:12px;padding:12px 16px;margin-bottom:14px}
  .wizbar .row{display:flex;align-items:center;gap:12px}
  .wizbar .idx{font-family:var(--mono);font-size:12px;font-weight:700;color:#fff;background:var(--ink);border-radius:6px;padding:3px 8px}
  .wizbar .idx.crit{background:var(--major)}
  .wizbar h3{font-size:15px;font-weight:600}
  .wizbar .of{margin-left:auto;font-family:var(--mono);font-size:12px;color:var(--ink-3)}
  .prog{height:6px;background:#e6ebef;border-radius:4px;overflow:hidden;margin-top:10px}
  .prog>i{display:block;height:100%;background:var(--brand);border-radius:4px;transition:width .2s}
  .stepcard{background:var(--paper);border:1px solid var(--line);border-radius:12px;overflow:hidden}
  .item{padding:13px 16px;border-top:1px solid var(--line)}
  .item:first-child{border-top:none}
  .item.sub{padding-left:38px;background:#fafbfc}
  .item.invalid{background:#fff6f4;box-shadow:inset 3px 0 0 var(--major)}
  .item .top{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
  .item .ref{font-family:var(--mono);font-size:12px;color:var(--ink-3);min-width:44px}
  .item .nm{font-weight:600;flex:1;min-width:150px}
  .item.sub .nm{font-weight:400}
  .item .measure{font-family:var(--mono);font-size:10px;color:var(--ink-3);margin-left:6px}
  .req-star{color:var(--major);font-weight:700;margin-left:3px}
  .rag{display:flex;border:1px solid var(--line);border-radius:9px;overflow:hidden}
  .rag button{width:38px;height:34px;display:grid;place-items:center;background:#fff;border:none;border-right:1px solid var(--line);cursor:pointer}
  .rag button:last-child{border-right:none}
  .rag button svg{width:15px;height:15px}
  .extra{margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .extra input[type=text],.extra input[type=number],.extra input[type=date],.extra textarea,.extra select{flex:1;min-width:200px}
  .chpick input{position:absolute;opacity:0;width:0;height:0}
  .chpick span{display:inline-block;font-size:12px;padding:4px 11px;border:1px solid var(--line);border-radius:999px;cursor:pointer;color:var(--ink-2);background:#fff}
  .chpick input:checked + span{background:var(--brand);color:#fff;border-color:var(--brand)}
  .chpick:hover span{border-color:var(--line-strong)}
  .photos{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
  .ph{position:relative;width:106px}
  .ph .media{position:relative;width:106px;height:80px;border-radius:8px;overflow:hidden;border:1px solid var(--line);background:#eef1f3;display:grid;place-items:center}
  .ph .media img{max-width:100%;max-height:100%;object-fit:contain}
  .ph .play{position:absolute;inset:0;display:grid;place-items:center;color:#fff;font-size:24px;text-shadow:0 1px 5px #000b;pointer-events:none}
  .ph .prog{font-family:var(--mono);font-size:12px;color:var(--ink-3)}
  .ph .x{position:absolute;top:3px;right:3px;width:18px;height:18px;border-radius:50%;background:#000a;color:#fff;border:none;cursor:pointer;font-size:11px;line-height:1;z-index:2}
  .ph .cap{width:106px;margin-top:4px;font-size:11px;padding:4px 6px;border:1px solid var(--line);border-radius:6px;box-sizing:border-box}
  .ph.add{width:106px;height:80px;display:grid;place-items:center;align-content:center;cursor:pointer;color:var(--ink-3);border:1px dashed var(--line-strong);border-radius:8px;background:#fff}
  .ph.add svg{width:20px;height:20px}
  .ph.add small{font-size:9px;letter-spacing:.05em;text-transform:uppercase;margin-top:3px}
  .savebar{position:sticky;bottom:0;background:#ffffffe8;backdrop-filter:blur(6px);border:1px solid var(--line);border-radius:12px;padding:12px 16px;display:flex;gap:10px;align-items:center;margin-top:16px;flex-wrap:wrap}
  .wizmsg{color:var(--major);font-size:12.5px;font-weight:500}
  canvas.sig{width:100%;max-width:420px;height:130px;border:1px dashed var(--line-strong);border-radius:10px;background:#fafbfc;touch-action:none}
  @media(max-width:640px){
    .capbar{padding:14px;gap:14px}
    .capbar > div:last-child{gap:14px}
    .capbar .metric .n{font-size:22px}
    .capbar .vid h2{font-size:18px}
    .item .top{gap:8px}
    .item .nm{min-width:120px}
    .extra input[type=text],.extra input[type=number],.extra input[type=date],.extra textarea,.extra select{min-width:140px}
    .savebar{gap:8px;padding:10px 12px}
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
  $v = $report['vehicle'] ?? []; $ins = $report['inspection']; $meta = $report['meta'] ?? []; $vs = $compute['verdict']['status'];
  $sections = $report['sections'];
  $stepTitles = array_map(fn($s) => $s['title'], $sections);
  $stepTitles[] = 'Conclusion & Sign-off';
  $total = count($stepTitles);
?>

<div class="capbar">
  <?php if (! empty($stockImage)): ?>
    <img src="<?= esc($stockImage) ?>" alt="Reference" title="Reference · ideal condition"
         style="height:64px;width:auto;max-width:110px;object-fit:contain;border-radius:8px;background:#fff;margin-right:16px;flex:none">
  <?php endif; ?>
  <div class="vid">
    <div class="yr"><?= esc(($v['year'] ?? '') . ' · ' . ($v['colour'] ?? '')) ?></div>
    <h2><?= esc(trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) ?: 'Vehicle') ?></h2>
    <div class="mono" style="font-size:11px;color:#8395a6"><?= esc($v['plate'] ?? $v['vin'] ?? '') ?></div>
  </div>
  <div style="margin-left:auto;display:flex;gap:22px;align-items:center">
    <div class="metric"><div class="n" style="color:var(--<?= $vs ?>)"><?= $compute['score'] ?></div><div class="l">Condition</div></div>
    <div class="metric"><div class="n" style="color:var(--major)"><?= $compute['counts']['major'] ?></div><div class="l">Major</div></div>
    <div class="metric"><div class="n" style="color:var(--lesser)"><?= $compute['counts']['lesser'] ?></div><div class="l">Lesser</div></div>
    <div class="metric"><div class="n" style="color:var(--minor)"><?= $compute['counts']['minor'] ?></div><div class="l">Minor</div></div>
  </div>
</div>

<form method="post" action="<?= site_url('inspections/' . $uid) ?>" id="captureForm"
      x-data="{ step:0, total:<?= $total ?>, titles:<?= esc(json_encode($stepTitles), 'attr') ?> }">
  <?= csrf_field() ?>

  <!-- Wizard header -->
  <div class="wizbar">
    <div class="row">
      <span class="idx" x-text="step+1"></span>
      <h3 x-text="titles[step]"></h3>
      <span class="of">Step <span x-text="step+1"></span> of <?= $total ?></span>
    </div>
    <div class="prog"><i :style="`width:${((step+1)/total)*100}%`"></i></div>
  </div>

  <!-- Section steps -->
  <?php foreach ($sections as $si => $section): ?>
    <div class="stepcard" data-step="<?= $si ?>" x-show="step===<?= $si ?>">
      <?php foreach ($section['items'] as $item): ?>
        <?php
          $id = $item['id']; $type = $item['type'];
          $choices = $item['choices'] ?? [];
          $isValueType = in_array($type, ['text','textarea','number','date','dropdown','radio','checkbox'], true);
          $reqInput = ! empty($item['required']) && ($choices || $isValueType);
        ?>
        <div class="item <?= $item['sub']?'sub':'' ?>" data-req="<?= $reqInput?1:0 ?>" x-data="{r:'<?= esc($item['status']?:'ok') ?>'}">
          <div class="top">
            <span class="ref"><?= esc($item['ref']) ?></span>
            <span class="nm"><?= esc($item['name']) ?><?php if($reqInput): ?><span class="req-star">*</span><?php endif; ?><?php if($item['measure']): ?><span class="measure">[<?= esc($item['measure']) ?>]</span><?php endif; ?></span>

            <?php if ($type === 'rating'): ?>
              <input type="hidden" name="rating[<?= $id ?>]" x-model="r">
              <div class="rag">
                <?php foreach (['ok','minor','lesser','major'] as $key): ?>
                  <button type="button" @click="r='<?= $key ?>'"
                    :style="r==='<?= $key ?>' ? 'background:var(--<?= $key ?>);color:#fff' : 'color:var(--<?= $key ?>)'"
                    title="<?= esc(CHECKA_RATINGS[$key]['label']) ?>">
                    <?php if($key==='ok'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>
                    <?php else: ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg><?php endif; ?>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="extra">
            <?php if (in_array($type, ['text','number','date'], true)): ?>
              <input type="<?= $type ?>" name="text[<?= $id ?>]" value="<?= esc($item['text']) ?>" placeholder="<?= esc($item['measure'] ? 'Reading ('.$item['measure'].')' : 'Value…') ?>">
            <?php elseif ($type === 'textarea'): ?>
              <textarea name="text[<?= $id ?>]" rows="2" placeholder="Notes…"><?= esc($item['text']) ?></textarea>
            <?php elseif (in_array($type, ['radio','dropdown','checkbox'], true)):
              $optRow = $item['field_id'] ? model(\App\Models\FieldModel::class)->find($item['field_id']) : null;
              $opts   = $optRow ? \App\Models\FieldModel::options($optRow) : [];
            ?>
              <?php if ($type === 'dropdown'): ?>
                <select name="text[<?= $id ?>]">
                  <option value="">— choose —</option>
                  <?php foreach ($opts as $o): ?><option <?= $item['text']===$o?'selected':'' ?>><?= esc($o) ?></option><?php endforeach; ?>
                </select>
              <?php else: ?>
                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
                  <?php foreach ($opts as $o): ?>
                    <label style="display:flex;gap:5px;align-items:center;font-size:13px">
                      <input type="<?= $type==='radio'?'radio':'checkbox' ?>" name="text[<?= $id ?>]<?= $type==='checkbox'?'[]':'' ?>" value="<?= esc($o) ?>" <?= $item['text']!==null && str_contains((string)$item['text'],$o)?'checked':'' ?>> <?= esc($o) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>

            <?php if ($choices): ?>
              <div style="flex-basis:100%;display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin:2px 0 4px">
                <span class="mono" style="font-size:10px;color:var(--ink-3);text-transform:uppercase;letter-spacing:.08em;margin-right:2px">Assessment</span>
                <?php foreach ($choices as $opt): ?>
                  <label class="chpick"><input type="radio" name="choice[<?= $id ?>]" value="<?= esc($opt) ?>" <?= ($item['choice'] ?? '')===$opt?'checked':'' ?>><span><?= esc($opt) ?></span></label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if ($type === 'rating'): ?>
              <input type="text" name="comment[<?= $id ?>]" value="<?= esc($item['comment']) ?>" placeholder="Comment / finding…">
              <div class="mono" style="font-size:12px;color:var(--ink-3);display:flex;align-items:center;gap:4px">R
                <input type="number" name="cost[<?= $id ?>]" value="<?= esc($item['cost']) ?>" placeholder="0" style="width:90px;min-width:0">
              </div>
            <?php endif; ?>
          </div>

          <?php if ($type !== 'signature'): ?>
          <div class="photos" data-value="<?= $id ?>" data-ref="<?= esc($item['ref']) ?>" data-name="<?= esc($item['name']) ?>">
            <?php foreach ($item['photos'] as $p): ?>
              <div class="ph" data-id="<?= $p['id'] ?>">
                <div class="media">
                  <img src="<?= $p['thumb'] ?>" alt="">
                  <?php if ($p['video']): ?><span class="play">▶</span><?php endif; ?>
                  <button type="button" class="x" data-del="<?= $p['id'] ?>">×</button>
                </div>
                <input type="text" class="cap" data-id="<?= $p['id'] ?>" value="<?= esc($p['caption']) ?>" placeholder="Caption…">
              </div>
            <?php endforeach; ?>
            <label class="ph add" title="Take a photo now">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 16l-5-5L5 19"/></svg>
              <small>Photo</small>
              <input type="file" accept="image/*" capture="environment" hidden class="upl" data-kind="image">
            </label>
            <label class="ph add" title="Record a video now">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="6" width="14" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3z"/></svg>
              <small>Video</small>
              <input type="file" accept="video/*" capture="environment" hidden class="upl" data-kind="video">
            </label>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <!-- Conclusion step -->
  <div class="stepcard" data-step="<?= $total-1 ?>" x-show="step===<?= $total-1 ?>">
    <div class="card-b grid cols-2" style="gap:18px;padding:18px">
      <div>
        <label class="fld"><span>Conclusion</span>
          <textarea name="conclusion" rows="5"><?= esc($ins['conclusion']) ?></textarea>
        </label>
        <div class="grid cols-2" style="gap:12px;margin-top:12px">
          <label class="fld"><span>Dealer / Seller</span><input type="text" name="meta_dealer" value="<?= esc($meta['dealer'] ?? '') ?>"></label>
          <label class="fld"><span>Mechanic</span><input type="text" name="meta_mechanic" value="<?= esc($meta['mechanic'] ?? '') ?>"></label>
          <label class="fld"><span>Odometer (before)</span><input type="text" name="meta_km_before" value="<?= esc($meta['km_before'] ?? '') ?>"></label>
          <label class="fld"><span>Odometer (after)</span><input type="text" name="meta_km_after" value="<?= esc($meta['km_after'] ?? '') ?>"></label>
        </div>
      </div>
      <div>
        <div class="fld"><span>Mechanic Signature</span>
          <canvas class="sig" id="sig"></canvas>
          <?php if ($ins['signature_path']): ?><div class="mono" style="font-size:11px;color:var(--ink-3);margin-top:5px">A signature is on file — draw again to replace.</div><?php endif; ?>
          <input type="hidden" name="signature" id="sigData">
          <button type="button" class="btn ghost sm" id="sigClear" style="margin-top:8px">Clear signature</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Nav / save bar -->
  <div class="savebar">
    <button type="button" class="btn ghost" @click="step>0 && step--" x-show="step>0">← Back</button>
    <button type="button" class="btn" @click="if(validateStep(step)){ step++; window.scrollTo({top:0,behavior:'smooth'}); }" x-show="step < total-1">Next →</button>
    <button type="button" class="btn dark" @click="completeInspection()" x-show="step === total-1">Complete &amp; Generate Report</button>
    <span class="wizmsg" id="wizMsg"></span>
    <div style="margin-left:auto"></div>
    <button type="submit" class="btn ghost">Save Progress</button>
    <a class="btn ghost" href="<?= site_url('reports/'.$uid) ?>">Preview Report</a>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
const CSRF_HEADER = "<?= csrf_header() ?>";
const CSRF_HASH   = "<?= csrf_hash() ?>";
const UPLOAD_URL  = "<?= site_url('inspections/'.$uid.'/photos') ?>";
const DEL_URL     = id => "<?= site_url('photos') ?>/" + id + "/delete";
const COMPLETE_URL = "<?= site_url('inspections/'.$uid.'/complete') ?>";
const msgEl = () => document.getElementById('wizMsg');

// A required item is "filled" if it has a checked assessment/radio, a chosen
// value, or (rating without choices) is always satisfied.
function itemFilled(item){
  const choice = item.querySelectorAll('input[name^="choice["]');
  if (choice.length) return [...choice].some(c => c.checked);
  const boxes = item.querySelectorAll('input[type="checkbox"][name^="text["]');
  if (boxes.length) return [...boxes].some(c => c.checked);
  const val = item.querySelector('input[name^="text["],select[name^="text["],textarea[name^="text["]');
  if (val) return val.value.trim() !== '';
  return true; // rating without choices
}

function validateStep(i){
  const card = document.querySelector(`.stepcard[data-step="${i}"]`);
  if (!card) return true;
  let firstBad = null;
  card.querySelectorAll('.item[data-req="1"]').forEach(item => {
    const ok = itemFilled(item);
    item.classList.toggle('invalid', !ok);
    if (!ok && !firstBad) firstBad = item;
  });
  if (firstBad){
    msgEl().textContent = 'Please complete the highlighted required item(s) before continuing.';
    firstBad.scrollIntoView({behavior:'smooth', block:'center'});
    return false;
  }
  msgEl().textContent = '';
  return true;
}

// Validate every step; return index of first invalid step, or -1.
function firstInvalidStep(total){
  for (let i=0;i<total-1;i++){ if (!validateStepSilent(i)) return i; }
  return -1;
}
function validateStepSilent(i){
  const card = document.querySelector(`.stepcard[data-step="${i}"]`);
  if (!card) return true;
  let ok = true;
  card.querySelectorAll('.item[data-req="1"]').forEach(item => {
    const good = itemFilled(item);
    item.classList.toggle('invalid', !good);
    if (!good) ok = false;
  });
  return ok;
}

// Alpine helpers (attached to window so x-data can call them)
window.validateStep = validateStep;
window.completeInspection = function(){
  const form = document.getElementById('captureForm');
  const total = <?= $total ?>;
  const bad = firstInvalidStep(total);
  if (bad >= 0){
    // jump Alpine to the offending step
    try { window.Alpine.$data(form).step = bad; } catch(e){}
    msgEl().textContent = 'Some required items are not filled — jumped to the first one.';
    window.scrollTo({top:0,behavior:'smooth'});
    return;
  }
  form.action = COMPLETE_URL;
  form.submit();
};

const CAPTION_URL = id => "<?= site_url('photos') ?>/" + id + "/caption";

// Decode an image File into an <img> (Safari decodes HEIC here natively).
function loadImage(file){
  return new Promise((res,rej)=>{
    const img=new Image(), url=URL.createObjectURL(file);
    img.onload=()=>{URL.revokeObjectURL(url);res(img);};
    img.onerror=()=>{URL.revokeObjectURL(url);rej(new Error('decode'));};
    img.src=url;
  });
}
// Downscale + re-encode to JPEG in the browser → small, fast upload; also
// converts HEIC → JPEG so the server never has to.
async function compressImage(file, maxDim=1600, q=0.82){
  try{
    const img=await loadImage(file);
    let w=img.naturalWidth||img.width, h=img.naturalHeight||img.height;
    const scale=Math.min(1, maxDim/Math.max(w,h));
    w=Math.round(w*scale); h=Math.round(h*scale);
    const cv=document.createElement('canvas'); cv.width=w; cv.height=h;
    cv.getContext('2d').drawImage(img,0,0,w,h);
    const blob=await new Promise(r=>cv.toBlob(r,'image/jpeg',q));
    return blob||file;
  }catch(e){ return file; }               // fall back to the original on any failure
}
// Grab the first frame of a video as a JPEG poster (for thumbnails / PDF).
function videoPoster(file){
  return new Promise((resolve)=>{
    try{
      const v=document.createElement('video'); v.muted=true; v.playsInline=true; v.preload='metadata';
      const url=URL.createObjectURL(file);
      const done=b=>{ URL.revokeObjectURL(url); resolve(b); };
      v.onloadeddata=()=>{ try{ v.currentTime=Math.min(0.2, (v.duration||1)/2); }catch(e){ done(null); } };
      v.onseeked=()=>{ try{
        const cv=document.createElement('canvas'); cv.width=v.videoWidth||640; cv.height=v.videoHeight||480;
        cv.getContext('2d').drawImage(v,0,0,cv.width,cv.height); cv.toBlob(b=>done(b),'image/jpeg',0.8);
      }catch(e){ done(null); } };
      v.onerror=()=>done(null);
      v.src=url;
    }catch(e){ resolve(null); }
  });
}
// POST with an upload-progress callback (fetch has no upload progress).
function uploadWithProgress(fd, onProgress){
  return new Promise((resolve,reject)=>{
    const xhr=new XMLHttpRequest();
    xhr.open('POST', UPLOAD_URL);
    xhr.setRequestHeader(CSRF_HEADER, CSRF_HASH);
    xhr.upload.onprogress=e=>{ if(e.lengthComputable&&onProgress) onProgress(Math.round(e.loaded/e.total*100)); };
    xhr.onload=()=>{ try{ resolve(JSON.parse(xhr.responseText)); }catch(err){ reject(err); } };
    xhr.onerror=()=>reject(new Error('network'));
    xhr.send(fd);
  });
}
function tileInner(id, thumb, isVideo, caption){
  const c=(caption||'').replace(/"/g,'&quot;');
  return `<div class="media"><img src="${thumb}">${isVideo?'<span class="play">▶</span>':''}`
       + `<button type="button" class="x" data-del="${id}">×</button></div>`
       + `<input type="text" class="cap" data-id="${id}" value="${c}" placeholder="Caption…">`;
}

// ---- Photo / video capture (delegated) ----
document.addEventListener('change', async (e) => {
  if (!e.target.classList.contains('upl')) return;
  const input=e.target;
  const file = input.files[0]; input.value=''; if (!file) return;
  const kind = input.dataset.kind || 'image';
  const wrap = input.closest('.photos');
  const caption = wrap.dataset.name || '';               // default caption = checklist item title

  const tile=document.createElement('div'); tile.className='ph';
  tile.innerHTML=`<div class="media"><span class="prog">0%</span></div>`;
  wrap.insertBefore(tile, wrap.querySelector('.add'));
  const setProg=p=>{ const el=tile.querySelector('.prog'); if(el) el.textContent=(kind==='video'&&p>=100)?'Saving…':p+'%'; };

  try{
    const fd=new FormData();
    if(kind==='image'){
      const blob=await compressImage(file);
      fd.append('photo', blob, 'photo.jpg');
    } else {
      const poster=await videoPoster(file);
      fd.append('photo', file, file.name||'video.mp4');
      if(poster) fd.append('poster', poster, 'poster.jpg');
    }
    fd.append('value_id', wrap.dataset.value);
    fd.append('ref', wrap.dataset.ref);
    fd.append('caption', caption);
    fd.append(CSRF_HEADER, CSRF_HASH);
    const j=await uploadWithProgress(fd, setProg);
    if(j&&j.ok){
      tile.dataset.id=j.id;
      tile.innerHTML=tileInner(j.id, j.thumb + '?t=' + Date.now(), j.video, j.caption || caption);
    } else { tile.remove(); alert((j&&j.error)||'Upload failed'); }
  }catch(err){ tile.remove(); alert('Upload failed'); }
});

// ---- Caption edit (debounced autosave) ----
let capTimer;
document.addEventListener('input', (e)=>{
  if(!e.target.classList.contains('cap')) return;
  const el=e.target; clearTimeout(capTimer);
  capTimer=setTimeout(()=>{
    const fd=new FormData(); fd.append('caption', el.value); fd.append(CSRF_HEADER, CSRF_HASH);
    fetch(CAPTION_URL(el.dataset.id), { method:'POST', body:fd, headers:{[CSRF_HEADER]:CSRF_HASH} });
  }, 600);
});

// ---- Photo delete ----
document.addEventListener('click', async (e) => {
  const id = e.target.dataset && e.target.dataset.del;
  if (!id) return;
  if (!confirm('Remove this photo?')) return;
  await fetch(DEL_URL(id), { method:'POST', headers:{[CSRF_HEADER]:CSRF_HASH,'X-Requested-With':'XMLHttpRequest'} });
  e.target.closest('.ph').remove();
});

// ---- Signature pad ----
(function(){
  const c = document.getElementById('sig'); if(!c) return;
  const ctx = c.getContext('2d');
  function size(){ const r=c.getBoundingClientRect(); c.width=r.width; c.height=r.height; ctx.strokeStyle='#12171d'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.lineJoin='round'; }
  setTimeout(size, 60); window.addEventListener('resize', size);
  let drawing=false;
  const pos=e=>{const r=c.getBoundingClientRect();const p=e.touches?e.touches[0]:e;return [p.clientX-r.left,p.clientY-r.top];};
  const start=e=>{drawing=true;const[x,y]=pos(e);ctx.beginPath();ctx.moveTo(x,y);};
  const move=e=>{if(!drawing)return;e.preventDefault();const[x,y]=pos(e);ctx.lineTo(x,y);ctx.stroke();};
  const end=()=>{if(!drawing)return;drawing=false;document.getElementById('sigData').value=c.toDataURL('image/png');};
  c.addEventListener('mousedown',start);c.addEventListener('mousemove',move);window.addEventListener('mouseup',end);
  c.addEventListener('touchstart',start);c.addEventListener('touchmove',move);c.addEventListener('touchend',end);
  document.getElementById('sigClear').addEventListener('click',()=>{ctx.clearRect(0,0,c.width,c.height);document.getElementById('sigData').value='';});
})();
</script>
<?= $this->endSection() ?>
