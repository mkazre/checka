<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Website<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Platform\Views\settings\_tabs', ['tabs' => $tabs, 'tab' => $tab]) ?>
<p class="muted small">The pricing page and sign-up on your website read your <a href="<?= site_url('packages') ?>">packages</a> live (only active, public ones). These are the extra texts around them.</p>
<form method="post" action="<?= site_url('settings/website') ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
    <div class="card">
      <h2>Pricing page</h2>
      <div class="f"><label>Note under the prices</label><input type="text" name="s[billingNote]" value="<?= esc($s['billingNote']) ?>"></div>
      <div class="f"><label>Add-ons <small>one per line: Name | Price</small></label><textarea name="s[addOns]" rows="6"><?= esc($s['addOns']) ?></textarea></div>
      <h2 style="margin-top:8px">Enterprise box</h2>
      <div class="f"><label>Title</label><input type="text" name="s[enterpriseName]" value="<?= esc($s['enterpriseName']) ?>"></div>
      <div class="f"><label>Text</label><input type="text" name="s[enterpriseTagline]" value="<?= esc($s['enterpriseTagline']) ?>"></div>
      <div class="f"><label>Button</label><input type="text" name="s[enterpriseCta]" value="<?= esc($s['enterpriseCta']) ?>"></div>
    </div>
    <div class="card">
      <h2>FAQ</h2>
      <div class="f"><label>Questions <small>question on the first line, answer below it; blank line between questions</small></label><textarea name="s[faq]" rows="16"><?= esc($s['faq']) ?></textarea></div>
      <h2>Contact form</h2>
      <div class="f"><label>Send enquiries to</label><input type="email" name="s[leadEmail]" value="<?= esc($s['leadEmail']) ?>" placeholder="defaults to your support email"><small>Every enquiry is also saved under <a href="<?= site_url('leads') ?>">Leads</a>.</small></div>
    </div>
  </div>
  <button class="btn">Save</button>
</form>
<?= $this->endSection() ?>
