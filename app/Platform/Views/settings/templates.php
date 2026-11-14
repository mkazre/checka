<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Email templates<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Platform\Views\settings\_tabs', ['tabs' => $tabs, 'tab' => $tab]) ?>
<div class="card">
  <p class="small" style="margin:0">Placeholders you can use: <?php foreach (\Platform\Services\PlatformSettings::PLACEHOLDERS as $p): ?><code><?= esc($p) ?></code> <?php endforeach; ?></p>
</div>
<form method="post" action="<?= site_url('settings/templates') ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
  <?php foreach (\Platform\Services\PlatformSettings::TEMPLATE_LABELS as $k => $label): ?>
    <div class="card">
      <h2><?= esc($label) ?></h2>
      <div class="f"><label>Subject</label><input type="text" name="s[<?= $k ?>][subject]" value="<?= esc($s[$k]['subject']) ?>"></div>
      <div class="f"><label>Message</label><textarea name="s[<?= $k ?>][body]" rows="7"><?= esc($s[$k]['body']) ?></textarea></div>
      <button class="btn sm sec" type="submit" form="test-<?= $k ?>">Send me a test</button>
    </div>
  <?php endforeach; ?>
  </div>
  <button class="btn">Save templates</button>
</form>
<?php foreach (\Platform\Services\PlatformSettings::TEMPLATE_LABELS as $k => $label): ?>
  <form id="test-<?= $k ?>" method="post" action="<?= site_url('settings/test-email') ?>"><?= csrf_field() ?><input type="hidden" name="template" value="<?= $k ?>"></form>
<?php endforeach; ?>
<p class="muted small">Tests use sample data and the last <b>saved</b> wording.</p>
<?= $this->endSection() ?>
