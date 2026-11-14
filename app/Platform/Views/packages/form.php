<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?><?= $id ? 'Edit package' : 'New package' ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$v   = static fn (string $k, $d = '') => old($k, $pkg[$k] ?? $d);
$mod = old('modules') ?? $modules;
$cats = [];
foreach ($catalog as $key => $m) {
    $cats[$m['status'] === 'roadmap' ? 'Coming soon — ' . $m['category'] : $m['category']][$key] = $m;
}
uksort($cats, static fn ($a, $b) => [str_starts_with($a, 'Coming'), $a] <=> [str_starts_with($b, 'Coming'), $b]);
?>
<div class="head">
  <div><h1><?= $id ? 'Edit “' . esc($pkg['name']) . '”' : 'New package' ?></h1>
  <p class="sub">Name it whatever you like. Tick the modules it includes and set its limits — leave a limit empty for unlimited.</p></div>
  <a class="btn sec" href="<?= site_url('packages') ?>">Back</a>
</div>

<form method="post" action="<?= site_url('packages/save' . ($id ? '/' . $id : '')) ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
    <div class="card">
      <h2>Name &amp; price</h2>
      <div class="grid g2">
        <div class="f"><label>Package name</label><input type="text" name="name" id="pname" value="<?= esc($v('name')) ?>" required maxlength="80" placeholder="e.g. Workshop Pro"></div>
        <div class="f"><label>Short code</label><input type="text" name="slug" id="pslug" value="<?= esc($v('slug')) ?>" maxlength="60" placeholder="auto"><small>Used in sign-up links, e.g. /signup?plan=workshop-pro</small></div>
      </div>
      <div class="f"><label>Tagline</label><input type="text" name="tagline" value="<?= esc($v('tagline')) ?>" maxlength="160" placeholder="Shown under the name on your pricing page"></div>
      <div class="f"><label>Description</label><textarea name="description" rows="3"><?= esc($v('description')) ?></textarea></div>
      <div class="grid g3">
        <div class="f"><label>Monthly price</label><input type="number" step="0.01" min="0" name="price_monthly" value="<?= esc($v('price_monthly', '0')) ?>"></div>
        <div class="f"><label>Annual price</label><input type="number" step="0.01" min="0" name="price_annual" value="<?= esc($v('price_annual', '0')) ?>"></div>
        <div class="f"><label>Free trial (days)</label><input type="number" min="0" name="trial_days" value="<?= esc($v('trial_days', $trialDefault)) ?>"><small>0 = no trial</small></div>
      </div>
    </div>
    <div class="card">
      <h2>Limits <small>empty = unlimited</small></h2>
      <div class="grid g2">
        <?php foreach ($limitDef as $k => $d): $lv = old('limits')[$k] ?? ($limits[$k] ?? ''); ?>
          <div class="f"><label><?= esc($d['name']) ?></label><input type="number" min="0" name="limits[<?= $k ?>]" value="<?= esc((string) $lv) ?>" placeholder="Unlimited"><small><?= esc($d['unit']) ?></small></div>
        <?php endforeach; ?>
      </div>
      <h2 style="margin-top:6px">Visibility</h2>
      <label class="check"><input type="checkbox" name="is_active" value="1" <?= $v('is_active', 1) ? 'checked' : '' ?>><span>Active<small>Can be assigned to workspaces. Untick to retire it.</small></span></label>
      <label class="check"><input type="checkbox" name="is_public" value="1" <?= $v('is_public', 1) ? 'checked' : '' ?>><span>Show on the public pricing page &amp; sign-up<small>Untick for private/custom deals you assign yourself.</small></span></label>
      <label class="check"><input type="checkbox" name="highlight" value="1" <?= $v('highlight', 0) ? 'checked' : '' ?>><span>Featured (“Most popular”)</span></label>
      <div class="f" style="max-width:160px"><label>Display order</label><input type="number" name="sort" value="<?= esc($v('sort', '0')) ?>"></div>
    </div>
  </div>

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
      <h2 style="margin:0">Modules in this package</h2>
      <div class="row-actions"><button type="button" class="btn sm sec" onclick="tick(true)">Select all</button><button type="button" class="btn sm sec" onclick="tick(false)">Clear</button></div>
    </div>
    <p class="muted small">“Coming soon” modules can be included now — they switch on for these workspaces automatically the day they ship.</p>
    <div class="mods">
      <?php foreach ($cats as $cat => $items): ?>
        <div class="cat"><?= esc($cat) ?></div>
        <?php foreach ($items as $key => $m): $core = ! empty($m['core']); ?>
          <label class="check"><input type="checkbox" class="mod" name="modules[]" value="<?= esc($key, 'attr') ?>" <?= ($core || in_array($key, (array) $mod, true)) ? 'checked' : '' ?> <?= $core ? 'disabled' : '' ?>>
            <span><?= esc($m['name']) ?><?= $core ? ' <span class="badge">Always included</span>' : '' ?><small><?= esc($m['description']) ?></small></span></label>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <button class="btn"><?= $id ? 'Save package' : 'Create package' ?></button>
</form>
<script>
function tick(on){document.querySelectorAll('input.mod:not([disabled])').forEach(c=>c.checked=on)}
(function(){const n=document.getElementById('pname'),s=document.getElementById('pslug');let touched=!!s.value;
s.addEventListener('input',()=>touched=true);n.addEventListener('input',()=>{if(!touched)s.value=n.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')})})();
</script>
<?= $this->endSection() ?>
