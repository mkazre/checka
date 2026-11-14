<?= $this->extend('Platform\Views\layout') ?>
<?= $this->section('title') ?>Team<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="head"><div><h1>Team</h1><p class="sub">People who can sign in to this platform panel. Staff can manage workspaces and packages; only owners can change gateways, email, general settings and the team.</p></div></div>
<div class="grid g2">
  <div class="tbl">
    <table>
      <thead><tr><th>Name</th><th>Role</th><th>Last sign-in</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($admins as $a): ?>
        <tr style="<?= $a['active'] ? '' : 'opacity:.5' ?>">
          <td><b><?= esc($a['name']) ?></b><div class="muted small"><?= esc($a['email']) ?></div></td>
          <td><?= $a['role'] === 'owner' ? 'Owner' : 'Staff' ?></td>
          <td class="small muted"><?= pf_date($a['last_login_at'], true) ?></td>
          <td class="right"><?php if ((int) $a['id'] !== (int) pf_admin()['id']): ?>
            <form method="post" action="<?= site_url('admins/' . $a['id'] . '/toggle') ?>"><?= csrf_field() ?><button class="btn sm sec"><?= $a['active'] ? 'Disable' : 'Enable' ?></button></form>
          <?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <form class="card" method="post" action="<?= site_url('admins') ?>">
    <?= csrf_field() ?>
    <h2>Add a login</h2>
    <div class="f"><label>Name</label><input type="text" name="name" value="<?= esc(old('name')) ?>" required></div>
    <div class="f"><label>Email</label><input type="email" name="email" value="<?= esc(old('email')) ?>" required></div>
    <div class="f"><label>Password</label><input type="text" name="password" minlength="10" required autocomplete="new-password"><small>At least 10 characters. Share it securely; they can change it under their account.</small></div>
    <div class="f"><label>Role</label><select name="role"><option value="staff">Staff</option><option value="owner">Owner</option></select></div>
    <button class="btn">Add login</button>
  </form>
</div>
<?= $this->endSection() ?>
