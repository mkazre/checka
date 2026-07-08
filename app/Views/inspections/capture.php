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
  .ph .aiph{position:absolute;bottom:3px;right:3px;background:var(--brand);color:#fff;border:none;border-radius:5px;font-size:9px;font-weight:700;letter-spacing:.04em;padding:2px 5px;cursor:pointer;z-index:2}
  .ph .aiph:disabled{opacity:.6}
  .ph .ann{position:absolute;bottom:3px;left:3px;background:#2d2d2d;color:#fff;border:none;border-radius:5px;font-size:11px;line-height:1;padding:3px 6px;cursor:pointer;z-index:2}
  /* annotation editor */
  .ann-modal{position:fixed;inset:0;background:#111c;z-index:9998;display:flex;flex-direction:column}
  .ann-bar{display:flex;gap:6px;flex-wrap:wrap;align-items:center;padding:10px;background:#1c2126}
  .ann-bar button{background:#2b3238;color:#cfd8e0;border:1px solid #3a444c;border-radius:8px;padding:8px 11px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
  .ann-bar button.on{background:var(--brand);color:#fff;border-color:var(--brand)}
  .ann-bar .sw{width:24px;height:24px;border-radius:6px;border:2px solid #3a444c;cursor:pointer;padding:0}
  .ann-bar .sw.on{border-color:#fff}
  .ann-bar .grow{flex:1}
  .ann-stage{flex:1;overflow:auto;display:flex;align-items:center;justify-content:center;padding:10px}
  .ann-stage canvas{max-width:100%;max-height:100%;background:#000;touch-action:none;box-shadow:0 6px 30px #0008}
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
                  <?php if (feature('aiPhotoAnalysis') && ! $p['video']): ?><button type="button" class="aiph" data-id="<?= $p['id'] ?>" title="Describe with AI">AI</button><?php endif; ?>
                  <?php if (feature('annotation') && ! $p['video']): ?><button type="button" class="ann" data-id="<?= $p['id'] ?>" title="Annotate">✎</button><?php endif; ?>
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
          <textarea name="conclusion" id="conclusion" rows="5"><?= esc($ins['conclusion']) ?></textarea>
        </label>
        <?php if (feature('aiConclusion')): ?>
        <button type="button" class="btn ghost sm" id="aiConclusionBtn" style="margin-top:8px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M12 3l1.9 4.6L18.5 9l-4.6 1.9L12 15l-1.9-4.1L5.5 9l4.6-1.4L12 3z"/></svg>
          Draft with AI
        </button>
        <span id="aiConclusionMsg" class="sub" style="font-size:11.5px;margin-left:8px"></span>
        <?php endif; ?>
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
    <button type="button" class="btn ghost" id="saveProgress">Save Progress</button>
    <a class="btn ghost" href="<?= site_url('reports/'.$uid) ?>">Preview Report</a>
  </div>
</form>

<?php if (feature('annotation')): ?>
<div id="annModal" class="ann-modal" style="display:none">
  <div class="ann-bar">
    <button type="button" data-tool="pen" class="on">✏ Pen</button>
    <button type="button" data-tool="arrow">↗ Arrow</button>
    <button type="button" data-tool="rect">▭ Box</button>
    <button type="button" data-tool="ellipse">◯ Circle</button>
    <button type="button" data-tool="text">T Text</button>
    <span style="width:10px"></span>
    <button type="button" class="sw on" data-color="#ff2d2d" style="background:#ff2d2d"></button>
    <button type="button" class="sw" data-color="#ffd400" style="background:#ffd400"></button>
    <button type="button" class="sw" data-color="#37b34a" style="background:#37b34a"></button>
    <button type="button" class="sw" data-color="#ffffff" style="background:#ffffff"></button>
    <button type="button" class="sw" data-color="#1c2126" style="background:#1c2126"></button>
    <span class="grow"></span>
    <button type="button" id="annUndo">↶ Undo</button>
    <button type="button" id="annClear">Clear</button>
    <button type="button" id="annCancel">Cancel</button>
    <button type="button" id="annSave" style="background:var(--brand);color:#fff;border-color:var(--brand)">Save</button>
  </div>
  <div class="ann-stage"><canvas id="annCanvas"></canvas></div>
</div>
<?php endif; ?>

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
const AI_PHOTO = <?= feature('aiPhotoAnalysis') ? 'true' : 'false' ?>;
const ANN_ON   = <?= feature('annotation') ? 'true' : 'false' ?>;
function tileInner(id, thumb, isVideo, caption){
  const c=(caption||'').replace(/"/g,'&quot;');
  return `<div class="media"><img src="${thumb}">${isVideo?'<span class="play">▶</span>':''}`
       + `<button type="button" class="x" data-del="${id}">×</button>`
       + ((AI_PHOTO && !isVideo)?`<button type="button" class="aiph" data-id="${id}" title="Describe with AI">AI</button>`:'')
       + ((ANN_ON && !isVideo)?`<button type="button" class="ann" data-id="${id}" title="Annotate">✎</button>`:'')
       + `</div>`
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

  // Build the request parts once so we can also queue them if offline.
  const parts = { fields: [['value_id', wrap.dataset.value], ['ref', wrap.dataset.ref], ['caption', caption]], files: [] };
  let previewUrl = null;
  try {
    if (kind === 'image') {
      const blob = await compressImage(file);
      parts.files.push({ name: 'photo', filename: 'photo.jpg', blob });
      previewUrl = URL.createObjectURL(blob);
    } else {
      const poster = await videoPoster(file);
      parts.files.push({ name: 'photo', filename: file.name || 'video.mp4', blob: file });
      if (poster) { parts.files.push({ name: 'poster', filename: 'poster.jpg', blob: poster }); previewUrl = URL.createObjectURL(poster); }
    }
    const fd = new FormData();
    parts.fields.forEach(([k, v]) => fd.append(k, v));
    parts.files.forEach(f => fd.append(f.name, f.blob, f.filename));
    fd.append(CSRF_HEADER, CSRF_HASH);
    const j = await uploadWithProgress(fd, setProg);
    if (j && j.ok) {
      tile.dataset.id = j.id;
      tile.innerHTML = tileInner(j.id, j.thumb + '?t=' + Date.now(), j.video, j.caption || caption);
    } else { tile.remove(); alert((j && j.error) || 'Upload failed'); }
  } catch (err) {
    // Network failure → queue it for auto-sync if the offline feature is on.
    if (window.Checka && !navigator.onLine) {
      await window.Checka.enqueue({ url: UPLOAD_URL, fields: parts.fields, files: parts.files });
      tile.innerHTML = `<div class="media">${previewUrl ? `<img src="${previewUrl}">` : ''}${kind === 'video' ? '<span class="play">▶</span>' : ''}`
        + `<span style="position:absolute;left:3px;bottom:3px;background:#2d2d2d;color:#fff;font-size:9px;padding:2px 6px;border-radius:6px">⏳ Queued</span></div>`
        + `<div class="cap" style="color:#6b7682;font-size:10.5px;text-align:center;padding:2px">Will sync when online</div>`;
    } else { tile.remove(); alert('Upload failed'); }
  }
});

// ---- Save Progress (AJAX; queues offline) ----
(function(){
  const btn = document.getElementById('saveProgress'); if(!btn) return;
  const SAVE_URL = "<?= site_url('inspections/'.$uid) ?>";
  btn.addEventListener('click', async () => {
    const form = document.getElementById('captureForm');
    const orig = btn.textContent; btn.disabled = true; btn.textContent = 'Saving…';
    try {
      const res = await fetch(SAVE_URL, { method:'POST', body:new FormData(form), headers:{'X-Requested-With':'XMLHttpRequest'} });
      if (!res.ok) throw new Error('http');
      msgEl().style.color = '#0f5f2e'; msgEl().textContent = 'Progress saved ✓';
    } catch(e) {
      if (window.Checka && !navigator.onLine) {
        const fields = [...new FormData(form).entries()].filter(([k,v]) => v !== CSRF_HASH).map(([k,v]) => [k, v]);
        await window.Checka.enqueue({ url: SAVE_URL, fields, files: [] });
        msgEl().style.color = '#2d2d2d'; msgEl().textContent = 'Saved offline — will sync when online.';
      } else { msgEl().style.color = '#b42318'; msgEl().textContent = 'Save failed — check your connection.'; }
    }
    btn.disabled = false; btn.textContent = orig;
  });
})();

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

// ---- AI: draft conclusion ----
(function(){
  const btn = document.getElementById('aiConclusionBtn'); if(!btn) return;
  const URL = "<?= site_url('inspections/'.$uid.'/ai-conclusion') ?>";
  const msg = document.getElementById('aiConclusionMsg');
  btn.addEventListener('click', async () => {
    const orig = btn.innerHTML; btn.disabled = true; btn.textContent = 'Drafting…'; msg.textContent = '';
    try {
      const res = await fetch(URL, { method:'POST', headers:{ [CSRF_HEADER]:CSRF_HASH }, body:new URLSearchParams({[CSRF_HEADER]:CSRF_HASH}) });
      const j = await res.json();
      if (j.ok && j.text) { document.getElementById('conclusion').value = j.text; msg.style.color='#0f5f2e'; msg.textContent='Drafted — review & edit.'; }
      else { msg.style.color='#b42318'; msg.textContent = j.error || 'Could not draft.'; }
    } catch(e){ msg.style.color='#b42318'; msg.textContent='Request failed.'; }
    btn.innerHTML = orig; btn.disabled = false;
  });
})();

// ---- AI: analyse a photo → caption ----
document.addEventListener('click', async (e) => {
  if (!e.target.classList.contains('aiph')) return;
  const btn = e.target, id = btn.dataset.id;
  btn.disabled = true; const orig = btn.textContent; btn.textContent = '…';
  try {
    const res = await fetch("<?= site_url('photos') ?>/"+id+"/ai-analyze", { method:'POST', headers:{ [CSRF_HEADER]:CSRF_HASH }, body:new URLSearchParams({[CSRF_HEADER]:CSRF_HASH}) });
    const j = await res.json();
    if (j.ok && j.text) {
      const cap = btn.closest('.ph').querySelector('.cap');
      if (cap) { cap.value = j.text; cap.style.background = '#eafaf0'; }
    } else { alert(j.error || 'Analysis failed'); }
  } catch(err){ alert('Analysis failed'); }
  btn.textContent = orig; btn.disabled = false;
});

// ---- Photo annotation editor ----
const Annot = (function(){
  const modal = document.getElementById('annModal'); if(!modal) return null;
  const canvas = document.getElementById('annCanvas'), ctx = canvas.getContext('2d');
  let baseImg=null, shapes=[], tool='pen', color='#ff2d2d', width=5, drawing=false, cur=null, curId=null;

  function drawShape(s){
    ctx.strokeStyle=s.color; ctx.fillStyle=s.color; ctx.lineWidth=s.width; ctx.lineCap='round'; ctx.lineJoin='round';
    if(s.type==='pen'){ ctx.beginPath(); s.points.forEach((p,i)=> i?ctx.lineTo(p.x,p.y):ctx.moveTo(p.x,p.y)); ctx.stroke(); }
    else if(s.type==='rect'){ ctx.strokeRect(s.x0,s.y0,s.x1-s.x0,s.y1-s.y0); }
    else if(s.type==='ellipse'){ ctx.beginPath(); ctx.ellipse((s.x0+s.x1)/2,(s.y0+s.y1)/2,Math.abs(s.x1-s.x0)/2,Math.abs(s.y1-s.y0)/2,0,0,7); ctx.stroke(); }
    else if(s.type==='arrow'){ arrow(s.x0,s.y0,s.x1,s.y1,s.width); }
    else if(s.type==='text'){ ctx.font='bold '+s.size+'px sans-serif'; ctx.fillText(s.text,s.x,s.y); }
  }
  function arrow(x0,y0,x1,y1,w){ ctx.beginPath(); ctx.moveTo(x0,y0); ctx.lineTo(x1,y1); ctx.stroke(); const a=Math.atan2(y1-y0,x1-x0), h=12+w*2; ctx.beginPath(); ctx.moveTo(x1,y1); ctx.lineTo(x1-h*Math.cos(a-0.4),y1-h*Math.sin(a-0.4)); ctx.lineTo(x1-h*Math.cos(a+0.4),y1-h*Math.sin(a+0.4)); ctx.closePath(); ctx.fill(); }
  function redraw(){ ctx.clearRect(0,0,canvas.width,canvas.height); if(baseImg) ctx.drawImage(baseImg,0,0,canvas.width,canvas.height); shapes.forEach(drawShape); if(cur) drawShape(cur); }
  function pos(e){ const r=canvas.getBoundingClientRect(); const p=e.touches?e.touches[0]:e; return { x:(p.clientX-r.left)*(canvas.width/r.width), y:(p.clientY-r.top)*(canvas.height/r.height) }; }
  function down(e){ e.preventDefault(); const p=pos(e);
    if(tool==='text'){ const t=prompt('Label text:'); if(t){ shapes.push({type:'text',color,size:Math.max(22,width*6),x:p.x,y:p.y,text:t}); redraw(); } return; }
    drawing=true; cur = tool==='pen' ? {type:'pen',color,width,points:[p]} : {type:tool,color,width,x0:p.x,y0:p.y,x1:p.x,y1:p.y}; }
  function move(e){ if(!drawing)return; e.preventDefault(); const p=pos(e); if(cur.type==='pen') cur.points.push(p); else { cur.x1=p.x; cur.y1=p.y; } redraw(); }
  function up(){ if(!drawing)return; drawing=false; if(cur) shapes.push(cur); cur=null; redraw(); }
  canvas.addEventListener('mousedown',down); canvas.addEventListener('mousemove',move); window.addEventListener('mouseup',up);
  canvas.addEventListener('touchstart',down,{passive:false}); canvas.addEventListener('touchmove',move,{passive:false}); canvas.addEventListener('touchend',up);

  modal.querySelectorAll('[data-tool]').forEach(b=> b.addEventListener('click',()=>{ tool=b.dataset.tool; modal.querySelectorAll('[data-tool]').forEach(x=>x.classList.toggle('on',x===b)); }));
  modal.querySelectorAll('.sw').forEach(b=> b.addEventListener('click',()=>{ color=b.dataset.color; modal.querySelectorAll('.sw').forEach(x=>x.classList.toggle('on',x===b)); }));
  document.getElementById('annUndo').addEventListener('click',()=>{ shapes.pop(); redraw(); });
  document.getElementById('annClear').addEventListener('click',()=>{ shapes=[]; redraw(); });
  document.getElementById('annCancel').addEventListener('click',close);
  document.getElementById('annSave').addEventListener('click',save);

  function open(id){ curId=id; shapes=[]; cur=null;
    const img=new Image();
    img.onload=()=>{ const cap=1600, sc=Math.min(1,cap/Math.max(img.naturalWidth,img.naturalHeight)); canvas.width=Math.round(img.naturalWidth*sc); canvas.height=Math.round(img.naturalHeight*sc); baseImg=img; redraw(); modal.style.display='flex'; };
    img.onerror=()=>alert('Could not load image');
    img.src="<?= site_url('media') ?>/"+id+"?t="+Date.now();
  }
  function close(){ modal.style.display='none'; baseImg=null; shapes=[]; }
  function save(){
    const btn=document.getElementById('annSave'); btn.disabled=true; btn.textContent='Saving…';
    canvas.toBlob(async (blob)=>{
      try{
        const fd=new FormData(); fd.append('image',blob,'annotated.jpg'); fd.append(CSRF_HEADER,CSRF_HASH);
        const res=await fetch("<?= site_url('photos') ?>/"+curId+"/annotate",{method:'POST',body:fd,headers:{[CSRF_HEADER]:CSRF_HASH}});
        const j=await res.json();
        if(j.ok){ const im=document.querySelector('.ph[data-id="'+curId+'"] .media img'); if(im) im.src=j.thumb; close(); }
        else alert(j.error||'Save failed');
      }catch(e){ alert('Save failed'); }
      btn.disabled=false; btn.textContent='Save';
    },'image/jpeg',0.9);
  }
  return { open };
})();
document.addEventListener('click',(e)=>{ if(e.target.classList.contains('ann') && Annot){ Annot.open(e.target.dataset.id); } });

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
