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

<!-- Sections -->
<?php foreach ($template['sections'] as $section): ?>
  <div class="card" style="margin-bottom:14px" x-data="{addField:false}">
    <div class="card-h" style="gap:12px">
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

    <?php foreach ($section['fields'] as $field): ?>
      <div x-data="{edit:false}">
        <div class="field-row">
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
              <label style="display:flex;gap:5px;align-items:center;font-size:12px"><input type="checkbox" name="is_sub" value="1" <?= $field['is_sub']?'checked':'' ?>> Sub-item</label>
              <label style="display:flex;gap:5px;align-items:center;font-size:12px"><input type="checkbox" name="allow_photos" value="1" <?= $field['allow_photos']?'checked':'' ?>> Allow photos</label>
            </div>
            <div style="margin-top:10px"><button class="btn sm" type="submit">Save field</button></div>
          </form>
        </div>
      </div>
    <?php endforeach; ?>

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
        </div>
        <div style="margin-top:10px"><button class="btn sm" type="submit">Add field</button></div>
      </form>
    </div>
  </div>
<?php endforeach; ?>

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
<?= $this->endSection() ?>
