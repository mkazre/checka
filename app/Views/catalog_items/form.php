<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $isEdit = ! empty($item['id']); $val = static fn ($k) => old($k) ?? ($item[$k] ?? ''); ?>

<div class="page-head">
  <div><h1><?= $isEdit ? 'Edit Item' : 'New Item' ?></h1><div class="sub">Changes apply to future purchase orders; existing ones keep what was ordered.</div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('items') ?>">Cancel</a>
</div>

<form method="post" action="<?= site_url($isEdit ? 'items/'.$item['id'] : 'items') ?>" class="card" style="max-width:640px">
  <?= csrf_field() ?>
  <div class="card-b grid" style="gap:14px">
    <label class="fld"><span>Item name *</span><input type="text" name="name" value="<?= esc($val('name')) ?>" required maxlength="255" placeholder="e.g. Front bumper cover"></label>
    <div class="grid cols-2" style="gap:14px">
      <label class="fld"><span>Part number</span><input type="text" name="part_no" value="<?= esc($val('part_no')) ?>" class="mono"></label>
      <label class="fld"><span>Default price (excl. VAT)</span><input type="number" name="default_price" step="0.01" value="<?= esc((string) $val('default_price')) ?>" placeholder="optional"></label>
    </div>
    <label class="fld"><span>Notes</span><input type="text" name="notes" value="<?= esc($val('notes')) ?>" maxlength="255" placeholder="e.g. OEM only, fits Golf 7"></label>
    <?php if ($isEdit): ?><div class="sub" style="font-size:12px;color:var(--ink-3)">Used <?= (int) $item['use_count'] ?> time(s)<?= $item['last_used_at'] ? ', last on ' . esc(substr($item['last_used_at'], 0, 10)) : '' ?>.</div><?php endif; ?>
    <div><button class="btn"><?= $isEdit ? 'Save changes' : 'Add item' ?></button></div>
  </div>
</form>

<?= $this->endSection() ?>
