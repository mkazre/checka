<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>New workspace<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="head">
  <div><h1>New workspace</h1><p class="sub">Creates the company's own workspace with its own data, admin login and modules. Setup finishes automatically within a minute.</p></div>
  <a class="btn sec" href="<?= site_url('tenants') ?>">Back</a>
</div>
<?php if (! $packages): ?><div class="flash err">Create an active package first under <a href="<?= site_url('packages/new') ?>">Packages</a>.</div><?php endif; ?>

<form method="post" action="<?= site_url('tenants') ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
    <div class="card">
      <h2>Company</h2>
      <div class="f"><label>Company name</label><input type="text" name="company_name" id="cname" value="<?= esc(old('company_name')) ?>" required></div>
      <div class="f"><label>Workspace address</label>
        <div style="display:flex;align-items:center;gap:6px"><input type="text" name="slug" id="slug" value="<?= esc(old('slug')) ?>" required pattern="[a-z0-9][a-z0-9-]{1,28}[a-z0-9]" style="max-width:220px"><span class="muted">.<?= esc(pf_root()) ?></span></div>
        <small>Lowercase letters, numbers and dashes. Can't be changed later.</small></div>
      <div class="grid g2">
        <div class="f"><label>Contact person</label><input type="text" name="contact_name" value="<?= esc(old('contact_name')) ?>"></div>
        <div class="f"><label>Phone</label><input type="text" name="phone" value="<?= esc(old('phone')) ?>"></div>
      </div>
      <div class="f"><label>VAT number</label><input type="text" name="vat_number" value="<?= esc(old('vat_number')) ?>"></div>
      <div class="f"><label>Internal notes</label><textarea name="notes" rows="2"><?= esc(old('notes')) ?></textarea></div>
    </div>
    <div>
      <div class="card">
        <h2>Admin login</h2>
        <div class="f"><label>Admin email</label><input type="email" name="email" value="<?= esc(old('email')) ?>" required><small>Becomes the workspace's first administrator.</small></div>
        <div class="f"><label>Admin password</label><input type="text" name="admin_password" value="" autocomplete="new-password" placeholder="Leave empty to generate one"></div>
      </div>
      <div class="card">
        <h2>Package</h2>
        <div class="f"><label>Package</label><select name="package_id" id="pkg" required>
          <?php foreach ($packages as $p): ?><option value="<?= $p['id'] ?>" data-trial="<?= (int) $p['trial_days'] ?>" <?= (int) old('package_id') === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?> — <?= pf_money($p['price_monthly']) ?>/month</option><?php endforeach; ?>
        </select></div>
        <div class="grid g2">
          <div class="f"><label>Billing</label><select name="billing_cycle"><option value="monthly">Monthly</option><option value="annual" <?= old('billing_cycle') === 'annual' ? 'selected' : '' ?>>Annual</option></select></div>
          <div class="f"><label>Start as</label><select name="start" id="start"><option value="trial">Free trial</option><option value="active" <?= old('start') === 'active' ? 'selected' : '' ?>>Active (paid)</option></select></div>
        </div>
        <div class="f" id="trialbox"><label>Trial length (days)</label><input type="number" min="1" name="trial_days" id="tdays" value="<?= esc(old('trial_days')) ?>"></div>
      </div>
      <?php if ((pf_admin()['role'] ?? '') === 'owner'): ?>
      <div class="card">
        <h2>Data</h2>
        <label class="check"><input type="radio" name="db_choice" value="new" <?= old('db_choice') !== 'existing' ? 'checked' : '' ?>><span>New, empty workspace</span></label>
        <label class="check"><input type="radio" name="db_choice" value="existing" <?= old('db_choice') === 'existing' ? 'checked' : '' ?>><span>Attach existing tables<small>For moving an existing install in (e.g. your current CheckA+ data). Enter its table prefix — empty means no prefix.</small></span></label>
        <div class="f"><input type="text" name="db_prefix" value="<?= esc(old('db_prefix')) ?>" placeholder="table prefix (optional)" style="max-width:220px"></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <button class="btn" <?= $packages ? '' : 'disabled' ?>>Create workspace</button>
</form>
<script>
(function(){const n=document.getElementById('cname'),s=document.getElementById('slug'),p=document.getElementById('pkg'),t=document.getElementById('tdays'),st=document.getElementById('start'),tb=document.getElementById('trialbox');
let touched=!!s.value;s.addEventListener('input',()=>touched=true);
n.addEventListener('input',()=>{if(!touched)s.value=n.value.toLowerCase().replace(/\b(pty|ltd|cc|inc)\b/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,30)});
const sync=()=>{if(p&&!t.dataset.touched)t.value=p.selectedOptions[0]?.dataset.trial||14;tb.style.display=st.value==='trial'?'':'none'};
t.addEventListener('input',()=>t.dataset.touched=1);p&&p.addEventListener('change',sync);st.addEventListener('change',sync);if(!t.value)sync();else tb.style.display=st.value==='trial'?'':'none';})();
</script>
<?= $this->endSection() ?>
