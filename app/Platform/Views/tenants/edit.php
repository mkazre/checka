<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Edit <?= esc($t['company_name']) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$pkgMods = [];
foreach ($packages as $p) {
    $pkgMods[$p['id']] = \Platform\Models\PackageModel::modules($p);
}
$pkgLim = [];
foreach ($packages as $p) {
    $pkgLim[$p['id']] = \Platform\Models\PackageModel::limits($p);
}
?>
<div class="head">
  <div><h1>Edit <?= esc($t['company_name']) ?></h1><p class="sub"><?= esc($t['slug'] . '.' . pf_root()) ?></p></div>
  <a class="btn sec" href="<?= site_url('tenants/' . $t['id']) ?>">Back</a>
</div>
<form method="post" action="<?= site_url('tenants/' . $t['id']) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="old_package_id" value="<?= (int) $t['package_id'] ?>">
  <div class="grid g2">
    <div class="card">
      <h2>Company</h2>
      <div class="f"><label>Company name</label><input type="text" name="company_name" value="<?= esc($t['company_name']) ?>" required></div>
      <div class="grid g2">
        <div class="f"><label>Contact person</label><input type="text" name="contact_name" value="<?= esc($t['contact_name']) ?>"></div>
        <div class="f"><label>Phone</label><input type="text" name="phone" value="<?= esc($t['phone']) ?>"></div>
      </div>
      <div class="grid g2">
        <div class="f"><label>Billing email</label><input type="email" name="email" value="<?= esc($t['email']) ?>" required></div>
        <div class="f"><label>VAT number</label><input type="text" name="vat_number" value="<?= esc($t['vat_number']) ?>"></div>
      </div>
      <div class="f"><label>Internal notes</label><textarea name="notes" rows="3"><?= esc($t['notes']) ?></textarea></div>
    </div>
    <div class="card">
      <h2>Subscription</h2>
      <div class="grid g2">
        <div class="f"><label>Package</label><select name="package_id" id="pkg">
          <?php foreach ($packages as $p): ?><option value="<?= $p['id'] ?>" <?= (int) $t['package_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?><?= $p['is_active'] ? '' : ' (retired)' ?></option><?php endforeach; ?>
        </select></div>
        <div class="f"><label>Billing</label><select name="billing_cycle"><option value="monthly">Monthly</option><option value="annual" <?= $t['billing_cycle'] === 'annual' ? 'selected' : '' ?>>Annual</option></select></div>
      </div>
      <div class="grid g2">
        <div class="f"><label>Trial ends</label><input type="date" name="trial_ends_at" value="<?= $t['trial_ends_at'] ? date('Y-m-d', strtotime($t['trial_ends_at'])) : '' ?>"></div>
        <div class="f"><label>Paid until</label><input type="date" name="current_period_end" value="<?= esc((string) $t['current_period_end']) ?>"></div>
      </div>
      <p class="hint">Status changes (pause, resume, cancel) are on the workspace page.</p>
    </div>
  </div>

  <div class="card">
    <h2>Limit overrides <small>for this workspace only</small></h2>
    <div class="tbl"><table>
      <thead><tr><th>Limit</th><th>Package value</th><th>This workspace</th></tr></thead>
      <tbody>
      <?php foreach ($limitDef as $k => $d):
          $has = array_key_exists($k, $over); $mode = ! $has ? 'package' : ($over[$k] === '' || $over[$k] === null ? 'unlimited' : 'custom'); ?>
        <tr>
          <td><?= esc($d['name']) ?></td>
          <td class="pkglim" data-k="<?= $k ?>"></td>
          <td style="display:flex;gap:8px;flex-wrap:wrap">
            <select name="override_mode[<?= $k ?>]" style="width:auto" onchange="this.nextElementSibling.style.display=this.value==='custom'?'':'none'">
              <option value="package" <?= $mode === 'package' ? 'selected' : '' ?>>Use package</option>
              <option value="custom" <?= $mode === 'custom' ? 'selected' : '' ?>>Custom</option>
              <option value="unlimited" <?= $mode === 'unlimited' ? 'selected' : '' ?>>Unlimited</option>
            </select>
            <input type="number" min="0" name="override[<?= $k ?>]" value="<?= $mode === 'custom' ? (int) $over[$k] : '' ?>" style="width:130px;<?= $mode === 'custom' ? '' : 'display:none' ?>">
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <div class="card">
    <h2>Extra modules <small>add-ons on top of the package</small></h2>
    <p class="muted small">Modules already in the selected package are greyed out.</p>
    <div class="mods">
      <?php foreach ($catalog as $key => $m): if (! empty($m['core'])) continue; ?>
        <label class="check"><input type="checkbox" class="xm" name="extra_modules[]" value="<?= esc($key, 'attr') ?>" <?= in_array($key, $extra, true) ? 'checked' : '' ?>>
          <span><?= esc($m['name']) ?><?= $m['status'] === 'roadmap' ? ' <span class="tag road">Coming soon</span>' : '' ?><small><?= esc($m['category']) ?></small></span></label>
      <?php endforeach; ?>
    </div>
  </div>
  <button class="btn">Save changes</button>
</form>
<script>
(function(){const mods=<?= json_encode($pkgMods) ?>,lims=<?= json_encode($pkgLim) ?>,sel=document.getElementById('pkg');
const sync=()=>{const inPkg=mods[sel.value]||[],l=lims[sel.value]||{};
document.querySelectorAll('.xm').forEach(c=>{const on=inPkg.includes(c.value);c.disabled=on;c.closest('label').style.opacity=on?.45:1});
document.querySelectorAll('.pkglim').forEach(td=>{const v=l[td.dataset.k];td.textContent=(v===null||v===undefined||v==='')?'Unlimited':Number(v).toLocaleString()})};
sel.addEventListener('change',sync);sync();})();
</script>
<?= $this->endSection() ?>
