<?= view('Platform\Views\public\_head', ['title' => 'Welcome']) ?>
<div class="card" style="max-width:560px;margin:20px auto;text-align:center">
  <div id="busy"><div class="spin"></div>
    <h1>Setting up <?= esc($t['company_name']) ?></h1>
    <p class="sub">We're creating your workspace, checklists and admin login. This usually takes under a minute.</p></div>
  <div id="ready" style="display:none">
    <div style="font-size:42px;line-height:1;margin-bottom:10px">✅</div>
    <h1>Your workspace is ready</h1>
    <p class="sub">Sign in with <b><?= esc($t['email']) ?></b> and the password you chose.<br>We've also emailed you the link.</p>
    <a class="btn" href="<?= esc($login, 'attr') ?>">Go to <?= esc($t['slug']) ?>.<?= esc(\Platform\Services\PlatformSettings::value('general', 'rootDomain')) ?></a>
  </div>
  <div id="slow" style="display:none" class="err">This is taking longer than usual. We've been notified — you'll get an email as soon as it's ready.</div>
  <?php if ($t['trial_ends_at']): ?><p class="mono" style="margin-top:22px">Free trial until <?= date('d M Y', strtotime($t['trial_ends_at'])) ?></p><?php endif; ?>
</div>
<script>
(function(){var n=0;function poll(){fetch('<?= site_url('api/signup-status/' . $token) ?>').then(function(r){return r.json()}).then(function(j){
  if(j.ready){document.getElementById('busy').style.display='none';document.getElementById('ready').style.display='block';return}
  if(j.error||++n>60){document.getElementById('slow').style.display='block'}
  setTimeout(poll,5000)}).catch(function(){setTimeout(poll,8000)})}poll()})();
</script>
</div></body></html>
