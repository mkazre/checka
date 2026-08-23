<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<style>
  .field-row{display:flex;align-items:center;gap:10px;padding:9px 12px;border-top:1px solid var(--line)}
  .field-row .ref{font-family:var(--mono);font-size:11px;color:var(--ink-3);min-width:44px}
  .field-row .nm{flex:1;font-weight:600}
  .field-row .badge{font-family:var(--mono);font-size:10px;padding:2px 7px;border-radius:5px;background:var(--ground);color:var(--ink-2)}
  .field-row .badge.role{background:#e6eef6;color:var(--minor)}
  .editform{padding:12px;background:#fafbfc;border-top:1px solid var(--line)}
  .editform .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
  @media(max-width:760px){.editform .grid{grid-template-columns:1fr 1fr}}
  @media(max-width:600px){
    .editform .grid{grid-template-columns:1fr}
    .editform .grid label[style*="span 2"],.editform .grid label[style*="span 3"]{grid-column:span 1 !important}
    .field-row{flex-wrap:wrap}
    .field-row .nm{flex-basis:100%}
  }
  .drag-handle{cursor:grab;color:var(--ink-3);font-size:15px;line-height:1;padding:2px 4px;flex:none;user-select:none;touch-action:none}
  .drag-handle:hover{color:var(--ink)}
  .tpl-section.dragging{opacity:.4}
  .field-item.dragging{opacity:.4}
  .field-item.drag-over-field{box-shadow:inset 0 2px 0 var(--brand)}
  .fields-list.drag-over-empty{background:#f0f7f2}
  #reorderMsg{font-size:12px;color:var(--ink-3);margin-left:10px}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1><?= esc($template['name']) ?></h1><div class="sub">Form builder · v<?= esc($template['version']) ?><?= $template['is_default'] ? ' · default template':'' ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('templates') ?>">← All templates</a>
</div>

<!-- Template meta -->
<div class="card" style="margin-bottom:16px">
  <div class="card-b">
    <form method="post" action="<?= site_url('templates/'.$template['id']) ?>" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <?= csrf_field() ?>
      <label class="fld" style="flex:1;min-width:220px"><span>Template name</span><input type="text" name="name" value="<?= esc($template['name']) ?>"></label>
      <label class="fld" style="flex:2;min-width:220px"><span>Description</span><input type="text" name="description" value="<?= esc($template['description']) ?>"></label>
      <label style="display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" name="is_active" value="1" <?= $template['is_active']?'checked':'' ?>> Active</label>
      <button class="btn" type="submit">Save</button>
    </form>
  </div>
</div>

<?php $types = $fieldTypes; $roles = $roles; ?>

<div style="display:flex;align-items:center;margin-bottom:6px">
  <span class="sub" style="font-size:12px;color:var(--ink-3)">Drag <span class="drag-handle" style="display:inline-block;vertical-align:middle">⠿</span> to reorder sections or fields — numbering updates automatically.</span>
  <span id="reorderMsg"></span>
</div>

<!-- Sections -->
<div id="sectionsList">
<?php foreach ($template['sections'] as $section): ?>
  <div class="card tpl-section" style="margin-bottom:14px" data-section-id="<?= $section['id'] ?>" x-data="{addField:false}">
    <div class="card-h" style="gap:12px">
      <span class="drag-handle section-handle" title="Drag to reorder section">⠿</span>
      <span class="pill"><?= $section['idx'] ?></span>
      <form method="post" action="<?= site_url('sections/'.$section['id']) ?>" style="display:flex;gap:10px;align-items:center;flex:1">
        <?= csrf_field() ?>
        <input type="text" name="title" value="<?= esc($section['title']) ?>" style="font-weight:600;max-width:340px">
        <label style="display:flex;gap:5px;align-items:center;font-size:12px;color:var(--ink-3)"><input type="checkbox" name="is_critical" value="1" <?= $section['is_critical']?'checked':'' ?>> Safety-critical</label>
        <button class="btn ghost sm" type="submit">Save</button>
      </form>
      <form method="post" action="<?= site_url('sections/'.$section['id'].'/delete') ?>" onsubmit="return confirm('Delete section and all its fields?')">
        <?= csrf_field() ?><button class="btn danger sm">Delete</button>
      </form>
    </div>

    <div class="fields-list" data-section-id="<?= $section['id'] ?>">
    <?php foreach ($section['fields'] as $field): ?>
      <div class="field-item" data-field-id="<?= $field['id'] ?>" x-data="{edit:false}">
        <div class="field-row">
          <span class="drag-handle field-handle" title="Drag to reorder / move field">⠿</span>
          <span class="ref"><?= esc($field['ref']) ?></span>
          <span class="nm"><?= esc($field['name']) ?><?php if($field['is_sub']): ?> <span style="color:var(--ink-3)">(sub)</span><?php endif; ?></span>
          <span class="badge"><?= esc($field['type']) ?></span>
          <?php if($field['role']): ?><span class="badge role"><?= esc($field['role']) ?></span><?php endif; ?>
          <button class="btn ghost sm" @click="edit=!edit">Edit</button>
          <form method="post" action="<?= site_url('fields/'.$field['id'].'/delete') ?>" onsubmit="return confirm('Delete this field?')">
            <?= csrf_field() ?><button class="btn danger sm">×</button>
          </form>
        </div>
        <div class="editform" x-show="edit" x-cloak>
          <form method="post" action="<?= site_url('fields/'.$field['id']) ?>">
            <?= csrf_field() ?>
            <div class="grid">
              <label class="fld"><span>Ref</span><input type="text" name="ref" value="<?= esc($field['ref']) ?>"></label>
              <label class="fld" style="grid-column:span 3"><span>Name</span><input type="text" name="name" value="<?= esc($field['name']) ?>"></label>
              <label class="fld"><span>Type</span><select name="type"><?php foreach($types as $k=>$lbl): ?><option value="<?= $k ?>" <?= $field['type']===$k?'selected':'' ?>><?= esc($lbl) ?></option><?php endforeach; ?></select></label>
              <label class="fld"><span>Role</span><select name="role"><?php foreach($roles as $k=>$lbl): ?><option value="<?= $k ?>" <?= (string)$field['role']===$k?'selected':'' ?>><?= esc($lbl) ?></option><?php endforeach; ?></select></label>
              <label class="fld"><span>Measure</span><input type="text" name="measure" value="<?= esc($field['measure']) ?>" placeholder="mm, km…"></label>
              <label class="fld"><span>Options (radio/dropdown)</span><input type="text" name="options" value="<?= esc(implode(', ', \App\Models\FieldModel::options($field))) ?>" placeholder="comma or newline separated"></label>
              <label class="fld" style="grid-column:span 2"><span>Assessment radio (standardised)</span><input type="text" name="choices" value="<?= esc(implode(', ', \App\Models\FieldModel::choicesList($field['choices'] ?? null))) ?>" placeholder="e.g. Good, Average, Poor, N/A"></label>
              <label style="display:flex;gap:5px;align-items:center;font-size:12px"><input type="checkbox" name="is_required" value="1" <?= !empty($field['is_required'])?'checked':'' ?>> Required</label>
              <label style="display:flex;gap:5px;align-items:center;font-size:12px"><input type="checkbox" name="is_sub" value="1" <?= $field['is_sub']?'checked':'' ?>> Sub-item</label>
              <label style="display:flex;gap:5px;align-items:center;font-size:12px"><input type="checkbox" name="allow_photos" value="1" <?= $field['allow_photos']?'checked':'' ?>> Allow photos</label>
            </div>
            <div style="margin-top:10px"><button class="btn sm" type="submit">Save field</button></div>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    </div>

    <!-- Add field -->
    <div class="field-row" style="justify-content:flex-start">
      <button class="btn ghost sm" @click="addField=!addField">+ Add field</button>
    </div>
    <div class="editform" x-show="addField" x-cloak>
      <form method="post" action="<?= site_url('sections/'.$section['id'].'/fields') ?>">
        <?= csrf_field() ?>
        <div class="grid">
          <label class="fld"><span>Ref</span><input type="text" name="ref" placeholder="<?= $section['idx'] ?>.x"></label>
          <label class="fld" style="grid-column:span 3"><span>Name</span><input type="text" name="name" placeholder="Field name" required></label>
          <label class="fld"><span>Type</span><select name="type"><?php foreach($types as $k=>$lbl): ?><option value="<?= $k ?>"><?= esc($lbl) ?></option><?php endforeach; ?></select></label>
          <label class="fld"><span>Role</span><select name="role"><?php foreach($roles as $k=>$lbl): ?><option value="<?= $k ?>"><?= esc($lbl) ?></option><?php endforeach; ?></select></label>
          <label class="fld"><span>Measure</span><input type="text" name="measure"></label>
          <label class="fld"><span>Options</span><input type="text" name="options" placeholder="comma separated"></label>
          <label class="fld" style="grid-column:span 2"><span>Assessment radio (standardised)</span><input type="text" name="choices" placeholder="e.g. Good, Average, Poor, N/A"></label>
          <label style="display:flex;gap:5px;align-items:center;font-size:12px"><input type="checkbox" name="is_required" value="1" checked> Required</label>
        </div>
        <div style="margin-top:10px"><button class="btn sm" type="submit">Add field</button></div>
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>

<!-- Add section -->
<div class="card">
  <div class="card-b">
    <form method="post" action="<?= site_url('templates/'.$template['id'].'/sections') ?>" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <?= csrf_field() ?>
      <label class="fld" style="flex:1;min-width:240px"><span>New section title</span><input type="text" name="title" placeholder="e.g. Hybrid / EV Checks" required></label>
      <label style="display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" name="is_critical" value="1"> Safety-critical</label>
      <button class="btn" type="submit">Add Section</button>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<style>[x-cloak]{display:none!important}</style>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
(function(){
  const CSRF_HEADER = "<?= csrf_header() ?>";
  const CSRF_HASH   = "<?= csrf_hash() ?>";
  const REORDER_URL = "<?= site_url('templates/reorder') ?>";
  const sectionsList = document.getElementById('sectionsList');
  const msgEl = document.getElementById('reorderMsg');
  if (!sectionsList) return;

  let draggingSection = null;
  let draggingField = null;

  function afterElement(container, y, selector) {
    const els = [...container.querySelectorAll(selector)];
    return els.reduce((closest, el) => {
      const box = el.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) return { offset, element: el };
      return closest;
    }, { offset: -Infinity, element: null }).element;
  }

  // ---- Sections: drag by .section-handle ----
  sectionsList.querySelectorAll('.tpl-section').forEach(card => {
    const handle = card.querySelector(':scope > .card-h .section-handle');
    if (!handle) return;
    handle.addEventListener('mousedown', () => card.setAttribute('draggable', 'true'));
    handle.addEventListener('touchstart', () => card.setAttribute('draggable', 'true'), { passive: true });
    card.addEventListener('dragstart', (e) => {
      draggingSection = card;
      e.dataTransfer.effectAllowed = 'move';
      setTimeout(() => card.classList.add('dragging'), 0);
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
      card.removeAttribute('draggable');
      draggingSection = null;
      persistOrder();
    });
  });
  sectionsList.addEventListener('dragover', (e) => {
    if (!draggingSection) return;
    e.preventDefault();
    const after = afterElement(sectionsList, e.clientY, '.tpl-section:not(.dragging)');
    if (after == null) sectionsList.appendChild(draggingSection);
    else sectionsList.insertBefore(draggingSection, after);
  });

  // ---- Fields: drag by .field-handle, can move between sections ----
  function wireFieldItem(item) {
    const handle = item.querySelector(':scope > .field-row .field-handle');
    if (!handle) return;
    handle.addEventListener('mousedown', () => item.setAttribute('draggable', 'true'));
    handle.addEventListener('touchstart', () => item.setAttribute('draggable', 'true'), { passive: true });
    item.addEventListener('dragstart', (e) => {
      draggingField = item;
      e.dataTransfer.effectAllowed = 'move';
      e.stopPropagation();
      setTimeout(() => item.classList.add('dragging'), 0);
    });
    item.addEventListener('dragend', (e) => {
      e.stopPropagation();
      item.classList.remove('dragging');
      item.removeAttribute('draggable');
      draggingField = null;
      persistOrder();
    });
  }
  sectionsList.querySelectorAll('.field-item').forEach(wireFieldItem);

  sectionsList.querySelectorAll('.fields-list').forEach(list => {
    list.addEventListener('dragover', (e) => {
      if (!draggingField) return;
      e.preventDefault();
      e.stopPropagation();
      const after = afterElement(list, e.clientY, '.field-item:not(.dragging)');
      if (after == null) list.appendChild(draggingField);
      else list.insertBefore(draggingField, after);
    });
  });

  // ---- Persist full order (sections + fields) and renumber ----
  let saving = false;
  function persistOrder() {
    if (saving) return;
    saving = true;
    if (msgEl) msgEl.textContent = 'Saving order…';

    const fd = new FormData();
    fd.append(CSRF_HEADER, CSRF_HASH);
    sectionsList.querySelectorAll(':scope > .tpl-section').forEach(card => {
      fd.append('sections[]', card.dataset.sectionId);
    });
    sectionsList.querySelectorAll('.fields-list').forEach(list => {
      const sid = list.dataset.sectionId;
      list.querySelectorAll(':scope > .field-item').forEach(item => {
        fd.append('fields[' + sid + '][]', item.dataset.fieldId);
      });
    });

    fetch(REORDER_URL, { method: 'POST', body: fd, headers: { [CSRF_HEADER]: CSRF_HASH, 'X-Requested-With': 'XMLHttpRequest' } })
      .then(res => res.json())
      .then(j => {
        if (j && j.ok) { location.reload(); }
        else { saving = false; if (msgEl) msgEl.textContent = 'Could not save order.'; }
      })
      .catch(() => { saving = false; if (msgEl) msgEl.textContent = 'Could not save order — check your connection.'; });
  }
})();
</script>
<?= $this->endSection() ?>
