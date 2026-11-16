<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Customer sign-in<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('Platform\Views\settings\_tabs', ['tabs' => $tabs, 'tab' => $tab]) ?>
<form method="post" action="<?= site_url('settings/auth') ?>">
  <?= csrf_field() ?>
  <div class="grid g2">
    <div class="card">
      <h2>Sign in with Google</h2>
      <label class="check"><input type="hidden" name="s[googleEnabled]" value="0"><input type="checkbox" name="s[googleEnabled]" value="1" <?= ! empty($s['googleEnabled']) ? 'checked' : '' ?>><span>Show “Continue with Google” on the sign-in page<small>Hidden automatically until the Client ID and Secret are filled in.</small></span></label>
      <div class="f"><label>Client ID</label><input type="text" name="s[googleClientId]" value="<?= esc($s['googleClientId']) ?>" placeholder="1234…apps.googleusercontent.com" autocomplete="off"></div>
      <div class="f"><label>Client secret</label><input type="password" name="s[googleClientSecret]" value="" autocomplete="new-password" placeholder="<?= ! empty($secretSet['googleClientSecret']) ? '•••••••• saved — leave empty to keep' : 'GOCSPX-…' ?>"></div>
      <button class="btn">Save</button>
    </div>
    <div class="card">
      <h2>How to get the Google keys</h2>
      <ol class="small" style="padding-left:18px;line-height:1.7;margin:0">
        <li>Open <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console → Credentials</a> (create a project if asked).</li>
        <li><b>OAuth consent screen</b>: User type <b>External</b>, app name “CheckA+”, your support email, then <b>Publish app</b>.</li>
        <li><b>Create credentials → OAuth client ID</b> → type <b>Web application</b>.</li>
        <li>Under <b>Authorised redirect URIs</b> add exactly:<br><code><?= esc(site_url('account/google/callback')) ?></code></li>
        <li>Copy the <b>Client ID</b> and <b>Client secret</b> into this form and save.</li>
      </ol>
      <p class="muted small" style="margin-top:12px">Customers can use Google only if their Google email is the same email their workspace login uses.</p>
    </div>
  </div>
</form>
<?= $this->endSection() ?>
