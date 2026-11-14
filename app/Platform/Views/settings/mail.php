<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Email sending<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Platform\Views\settings\_tabs', ['tabs' => $tabs, 'tab' => $tab]) ?>
<form method="post" action="<?= site_url('settings/mail') ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
    <div class="card">
      <h2>How the platform sends email</h2>
      <?php foreach (['mail' => ['Server mail', 'Uses the hosting server. Works on cPanel where outbound SMTP is blocked.'],
                      'brevo' => ['Brevo (HTTP API)', 'Best deliverability; free tier available. Works where SMTP is blocked.'],
                      'smtp' => ['SMTP', 'Your own mail server. Often blocked on shared hosting.']] as $k => [$l, $h]): ?>
        <label class="check"><input type="radio" name="s[transport]" value="<?= $k ?>" <?= $s['transport'] === $k ? 'checked' : '' ?>><span><?= $l ?><small><?= $h ?></small></span></label>
      <?php endforeach; ?>
      <div class="grid g2" style="margin-top:10px">
        <div class="f"><label>From email</label><input type="email" name="s[fromEmail]" value="<?= esc($s['fromEmail']) ?>" placeholder="no-reply@<?= esc(pf_root()) ?>"></div>
        <div class="f"><label>From name</label><input type="text" name="s[fromName]" value="<?= esc($s['fromName']) ?>"></div>
      </div>
    </div>
    <div>
      <div class="card">
        <h2>Brevo</h2>
        <div class="f"><label>API key</label><input type="password" name="s[brevoKey]" value="" autocomplete="new-password" placeholder="<?= ! empty($secretSet['brevoKey']) ? '•••••••• saved — leave empty to keep' : 'xkeysib-…' ?>"></div>
      </div>
      <div class="card">
        <h2>SMTP</h2>
        <div class="grid g2">
          <div class="f"><label>Host</label><input type="text" name="s[smtpHost]" value="<?= esc($s['smtpHost']) ?>"></div>
          <div class="f"><label>Port</label><input type="number" name="s[smtpPort]" value="<?= (int) $s['smtpPort'] ?>"></div>
          <div class="f"><label>Username</label><input type="text" name="s[smtpUser]" value="<?= esc($s['smtpUser']) ?>" autocomplete="off"></div>
          <div class="f"><label>Password</label><input type="password" name="s[smtpPass]" value="" autocomplete="new-password" placeholder="<?= ! empty($secretSet['smtpPass']) ? '•••••••• saved' : '' ?>"></div>
        </div>
        <div class="f" style="max-width:160px"><label>Encryption</label><select name="s[smtpCrypto]"><?php foreach (['tls', 'ssl', ''] as $c): ?><option value="<?= $c ?>" <?= $s['smtpCrypto'] === $c ? 'selected' : '' ?>><?= $c ?: 'none' ?></option><?php endforeach; ?></select></div>
      </div>
    </div>
  </div>
  <button class="btn">Save</button>
</form>
<form method="post" action="<?= site_url('settings/test-email') ?>" class="card" style="margin-top:22px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
  <?= csrf_field() ?>
  <b>Send a test email to</b>
  <input type="email" name="to" value="<?= esc(pf_admin()['email'] ?? '') ?>" style="max-width:280px">
  <button class="btn sec">Send test</button>
</form>
<?= $this->endSection() ?>
