<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<style>
  .capbar{background:var(--panel);color:#e9eef2;border-radius:14px;padding:18px 20px;margin-bottom:18px;display:flex;align-items:center;gap:22px;flex-wrap:wrap}
  .capbar .vid .yr{font-family:var(--mono);font-size:11px;letter-spacing:.2em;color:#8395a6;text-transform:uppercase}
  .capbar .vid h2{font-size:22px;font-weight:600;margin-top:2px}
  .capbar .metric{text-align:center}
  .capbar .metric .n{font-family:var(--display);font-weight:700;font-size:30px;line-height:1}
  .capbar .metric .l{font-family:var(--mono);font-size:9px;letter-spacing:.14em;color:#8395a6;text-transform:uppercase;margin-top:3px}
  .sec{margin-bottom:12px}
  .sec-head{display:flex;align-items:center;gap:11px;padding:13px 16px;cursor:pointer;background:var(--paper);border:1px solid var(--line);border-radius:12px}
  .sec-head .idx{font-family:var(--mono);font-size:12px;font-weight:700;color:#fff;background:var(--ink);border-radius:6px;padding:3px 8px}
  .sec-head .idx.crit{background:var(--major)}
  .sec-head h3{font-size:15px;font-weight:600}
  .sec-head .cnt{margin-left:auto;font-family:var(--mono);font-size:12px;color:var(--ink-3)}
  .sec-body{border:1px solid var(--line);border-top:none;border-radius:0 0 12px 12px;background:var(--paper)}
  .item{padding:13px 16px;border-top:1px solid var(--line)}
  .item.sub{padding-left:38px;background:#fafbfc}
  .item .top{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
  .item .ref{font-family:var(--mono);font-size:12px;color:var(--ink-3);min-width:44px}
  .item .nm{font-weight:600;flex:1;min-width:150px}
  .item.sub .nm{font-weight:400}
  .item .measure{font-family:var(--mono);font-size:10px;color:var(--ink-3);margin-left:6px}
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
  .photos{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
  .ph{position:relative;width:78px;height:60px;border-radius:8px;overflow:hidden;border:1px solid var(--line);background:#eef1f3}
  .ph img{max-width:100%;max-height:100%;object-fit:contain}
  .ph .x{position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:#000a;color:#fff;border:none;cursor:pointer;font-size:11px;line-height:1}
  .ph.add{display:grid;place-items:center;cursor:pointer;color:var(--ink-3);border-style:dashed}
  .ph.add svg{width:20px;height:20px}
  .savebar{position:sticky;bottom:0;background:#ffffffe8;backdrop-filter:blur(6px);border:1px solid var(--line);border-radius:12px;padding:12px 16px;display:flex;gap:10px;align-items:center;margin-top:16px}
  canvas.sig{width:100%;max-width:420px;height:130px;border:1px dashed var(--line-strong);border-radius:10px;background:#fafbfc;touch-action:none}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $v = $report['vehicle'] ?? []; $ins = $report['inspection']; $meta = $report['meta'] ?? []; $vs = $compute['verdict']['status']; ?>

<div class="capbar">
  <div class="vid">
    <div class="yr"><?= esc(($v['year'] ?? '') . ' · ' . ($v['colour'] ?? '')) ?></div>
    <h2><?= esc(trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) ?: 'Vehicle') ?></h2>
    <div class="mono" style="font-size:11px;color:#8395a6"><?= esc($v['plate'] ?? $v['vin'] ?? '') ?></div>
  </div>
  <div style="margin-left:auto;display:flex;gap:26px;align-items:center">
    <div class="metric"><div class="n" style="color:var(--<?= $vs ?>)"><?= $compute['score'] ?></div><div class="l">Condition</div></div>
    <div class="metric"><div class="n" style="color:var(--major)"><?= $compute['counts']['major'] ?></div><div class="l">Major</div></div>
    <div class="metric"><div class="n" style="color:var(--lesser)"><?= $compute['counts']['lesser'] ?></div><div class="l">Lesser</div></div>
    <div class="metric"><div class="n" style="color:var(--minor)"><?= $compute['counts']['minor'] ?></div><div class="l">Minor</div></div>
  </div>
</div>

<form method="post" action="<?= site_url('inspections/' . $uid) ?>" id="captureForm">
  <?= csrf_field() ?>

  <?php foreach ($report['sections'] as $section): ?>
    <?php $flagged = 0; foreach ($section['items'] as $it) { if (($it['type']==='rating') && ($it['status']??'ok')!=='ok') $flagged++; } ?>
    <div class="sec" x-data="{open:<?= $section['idx'] <= 2 ? 'true':'false' ?>}">
      <div class="sec-head" @click="open=!open">
        <span class="idx <?= !empty($section['is_critical'])?'crit':'' ?>"><?= $section['idx'] ?></span>
        <h3><?= esc($section['title']) ?></h3>
        <span class="cnt"><span x-show="!open">▸</span><span x-show="open">▾</span> <?= $flagged ? $flagged.' flagged' : 'clear' ?></span>
      </div>
      <div class="sec-body" x-show="open" x-collapse>
        <?php foreach ($section['items'] as $item): ?>
          <?php $id = $item['id']; $type = $item['type']; $opts = []; ?>
          <div class="item <?= $item['sub']?'sub':'' ?>" x-data="{r:'<?= esc($item['status']?:'ok') ?>'}">
            <div class="top">
              <span class="ref"><?= esc($item['ref']) ?></span>
              <span class="nm"><?= esc($item['name']) ?><?php if($item['measure']): ?><span class="measure">[<?= esc($item['measure']) ?>]</span><?php endif; ?></span>

              <?php if ($type === 'rating'): ?>
                <input type="hidden" name="rating[<?= $id ?>]" x-model="r">
                <div class="rag">
                  <?php foreach (['ok'=>'✓','minor'=>'!','lesser'=>'!','major'=>'!'] as $key=>$sym): ?>
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
                    <?php foreach ($opts as $i=>$o): ?>
                      <label style="display:flex;gap:5px;align-items:center;font-size:13px">
                        <input type="<?= $type==='radio'?'radio':'checkbox' ?>" name="text[<?= $id ?>]<?= $type==='checkbox'?'[]':'' ?>" value="<?= esc($o) ?>" <?= $item['text']!==null && str_contains((string)$item['text'],$o)?'checked':'' ?>> <?= esc($o) ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              <?php endif; ?>

              <?php $choices = $item['choices'] ?? []; if ($choices): ?>
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
            <div class="photos" data-value="<?= $id ?>" data-ref="<?= esc($item['ref']) ?>">
              <?php foreach ($item['photos'] as $p): ?>
                <div class="ph" data-id="<?= $p['id'] ?>">
                  <img src="<?= $p['thumb'] ?>" alt="">
                  <button type="button" class="x" data-del="<?= $p['id'] ?>">×</button>
                </div>
              <?php endforeach; ?>
              <label class="ph add">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 16l-5-5L5 19"/></svg>
                <input type="file" accept="image/*,video/*" hidden class="upl">
              </label>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Conclusion + sign-off -->
  <div class="card" style="margin-top:16px">
    <div class="card-h"><h3>Conclusion &amp; Sign-off</h3></div>
    <div class="card-b grid cols-2" style="gap:18px">
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

  <div class="savebar">
    <button class="btn" type="submit">Save Progress</button>
    <a class="btn ghost" href="<?= site_url('reports/'.$uid) ?>">Preview Report</a>
    <div style="margin-left:auto"></div>
    <button class="btn dark" type="submit" formaction="<?= site_url('inspections/'.$uid.'/complete') ?>">Complete &amp; Generate Report</button>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
const CSRF_HEADER = "<?= csrf_header() ?>";
const CSRF_HASH   = "<?= csrf_hash() ?>";
const UPLOAD_URL  = "<?= site_url('inspections/'.$uid.'/photos') ?>";
const DEL_URL     = id => "<?= site_url('photos') ?>/" + id + "/delete";

// ---- Photo upload (delegated) ----
document.addEventListener('change', async (e) => {
  if (!e.target.classList.contains('upl')) return;
  const file = e.target.files[0]; if (!file) return;
  const wrap = e.target.closest('.photos');
  const fd = new FormData();
  fd.append('photo', file);
  fd.append('value_id', wrap.dataset.value);
  fd.append('ref', wrap.dataset.ref);
  fd.append(CSRF_HEADER, CSRF_HASH);
  try {
    const res = await fetch(UPLOAD_URL, { method:'POST', body:fd, headers:{[CSRF_HEADER]:CSRF_HASH} });
    const j = await res.json();
    if (j.ok) {
      const div = document.createElement('div');
      div.className='ph'; div.dataset.id=j.id;
      div.innerHTML = `<img src="${j.thumb}"><button type="button" class="x" data-del="${j.id}">×</button>`;
      wrap.insertBefore(div, e.target.closest('.add'));
    } else { alert(j.error || 'Upload failed'); }
  } catch(err){ alert('Upload failed'); }
  e.target.value='';
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
  size(); window.addEventListener('resize', size);
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
