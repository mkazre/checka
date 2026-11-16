<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="page-head"><div><h1>My account</h1><div class="sub">Your name, sign-in email and password.</div></div></div>

<form method="post" action="<?= site_url('account') ?>" class="card" style="max-width:560px" autocomplete="off">
  <?= csrf_field() ?>
  <div class="card-b" style="display:grid;gap:14px">
    <label class="fld"><span>Your name</span>
      <input type="text" name="name" value="<?= esc(old('name') ?? $name) ?>" maxlength="80" placeholder="e.g. Thabo Mokoena"></label>
    <label class="fld"><span>Sign-in email</span>
      <input type="email" name="email" value="<?= esc(old('email') ?? (string) $user->email) ?>" required autocomplete="username"></label>

    <div style="border-top:1px solid var(--line);padding-top:14px">
      <b>Change password</b>
      <div class="sub" style="font-size:12.5px;margin-top:2px">Leave blank to keep your current password.</div>
    </div>
    <label class="fld"><span>New password</span><input type="password" name="password" minlength="8" autocomplete="new-password"></label>
    <label class="fld"><span>Type the new password again</span><input type="password" name="password_confirm" minlength="8" autocomplete="new-password"></label>

    <div style="border-top:1px solid var(--line);padding-top:14px">
      <label class="fld"><span>Current password <small style="font-weight:400">— needed to change your email or password</small></span>
        <input type="password" name="current_password" autocomplete="current-password"></label>
    </div>
    <div><button class="btn" type="submit">Save changes</button></div>
  </div>
</form>
<?= $this->endSection() ?>
