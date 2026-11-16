<?= view('Platform\Views\public\_head', ['title' => 'Choose a new password']) ?>
<div class="card" style="max-width:460px;margin:20px auto">
  <?php if ($done): ?>
    <h1>Password changed ✓</h1>
    <p class="sub">Your new password is set<?= $email ? ' for <b>' . esc($email) . '</b>' : '' ?>. Use it next time you sign in.</p>
    <a class="btn" href="<?= esc($next, 'attr') ?>">Continue to my workspace</a>
  <?php elseif (! $email): ?>
    <h1>This link has expired</h1>
    <p class="sub">Password reset links work once, for one hour. Ask for a new one on the sign-in page.</p>
    <a class="btn" href="<?= esc(rtrim(str_replace('://app.', '://', \Platform\Services\Billing::appUrl()), '/') . '/login', 'attr') ?>">Back to sign in</a>
  <?php else: ?>
    <h1>Choose a new password</h1>
    <p class="sub">For <b><?= esc($email) ?></b></p>
    <?php if ($error): ?><div class="err"><?= esc($error) ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('account/reset/' . $token) ?>">
      <?= csrf_field() ?>
      <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px">New password</label>
      <input type="password" name="password" required minlength="8" autocomplete="new-password" style="width:100%;padding:12px;border:1px solid #d6dce1;border-radius:10px;font:inherit;margin-bottom:14px">
      <label style="display:block;font-weight:600;font-size:13px;margin-bottom:6px">Type it again</label>
      <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password" style="width:100%;padding:12px;border:1px solid #d6dce1;border-radius:10px;font:inherit;margin-bottom:18px">
      <button class="btn" style="width:100%">Save new password</button>
    </form>
  <?php endif; ?>
</div>
</div></body></html>
