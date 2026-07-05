<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Users</h1><div class="sub">Admins manage templates &amp; users; mechanics capture inspections.</div></div>
</div>

<div class="grid cols-2">
  <div class="card">
    <div class="card-h"><h3>Team</h3></div>
    <div class="tbl-scroll">
    <table class="tbl">
      <thead><tr><th>User</th><th>Email</th><th>Role</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): $isAdmin = in_array('admin', $u['groups'], true); ?>
        <tr>
          <td><b><?= esc($u['username']) ?></b></td>
          <td class="mono" style="font-size:12px"><?= esc($u['email']) ?></td>
          <td>
            <form method="post" action="<?= site_url('users/'.$u['id'].'/role') ?>" style="display:flex;gap:6px;align-items:center">
              <?= csrf_field() ?>
              <select name="group" style="width:auto">
                <option value="mechanic" <?= !$isAdmin?'selected':'' ?>>Mechanic</option>
                <option value="admin" <?= $isAdmin?'selected':'' ?>>Administrator</option>
              </select>
              <button class="btn ghost sm">Update</button>
            </form>
          </td>
          <td style="text-align:right">
            <?php if ((int)auth()->id() !== (int)$u['id']): ?>
            <form method="post" action="<?= site_url('users/'.$u['id'].'/delete') ?>" onsubmit="return confirm('Delete user?')"><?= csrf_field() ?><button class="btn danger sm">Delete</button></form>
            <?php else: ?><span class="sub" style="font-size:12px;color:var(--ink-3)">you</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <div class="card">
    <div class="card-h"><h3>Add User</h3></div>
    <div class="card-b">
      <form method="post" action="<?= site_url('users') ?>" class="grid" style="gap:12px">
        <?= csrf_field() ?>
        <label class="fld"><span>Username</span><input type="text" name="username" required></label>
        <label class="fld"><span>Email</span><input type="email" name="email" required></label>
        <label class="fld"><span>Password</span><input type="password" name="password" required minlength="8"></label>
        <label class="fld"><span>Role</span><select name="group"><option value="mechanic">Mechanic</option><option value="admin">Administrator</option></select></label>
        <div><button class="btn" type="submit">Create User</button></div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
