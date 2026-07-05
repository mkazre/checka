<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Inspection Templates</h1><div class="sub">Build and manage the checklists mechanics capture against.</div></div>
</div>

<div class="grid cols-2">
  <div class="card">
    <div class="card-h"><h3>Your Templates</h3></div>
    <table class="tbl">
      <thead><tr><th>Name</th><th>Version</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($templates as $t): ?>
        <tr>
          <td><b><?= esc($t['name']) ?></b><?php if($t['is_default']): ?> <span class="pill">Default</span><?php endif; ?><div class="sub" style="font-size:12px;color:var(--ink-3)"><?= esc($t['description']) ?></div></td>
          <td class="mono">v<?= esc($t['version']) ?></td>
          <td><?= $t['is_active'] ? '<span class="pill completed">Active</span>' : '<span class="pill">Inactive</span>' ?></td>
          <td style="text-align:right;white-space:nowrap">
            <a class="btn ghost sm" href="<?= site_url('templates/'.$t['id'].'/builder') ?>">Edit</a>
            <?php if (! $t['is_default']): ?>
            <form method="post" action="<?= site_url('templates/'.$t['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this template?')">
              <?= csrf_field() ?><button class="btn danger sm">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-h"><h3>New Template</h3></div>
    <div class="card-b">
      <form method="post" action="<?= site_url('templates') ?>" class="grid" style="gap:14px">
        <?= csrf_field() ?>
        <label class="fld"><span>Name</span><input type="text" name="name" placeholder="e.g. Pre-purchase Inspection" required></label>
        <label class="fld"><span>Description</span><textarea name="description" rows="3" placeholder="Optional"></textarea></label>
        <div><button class="btn" type="submit">Create Template</button></div>
      </form>
      <p class="sub" style="margin-top:14px;font-size:12.5px;color:var(--ink-3)">Tip: duplicate the default 20-section checklist by editing it, or start blank and add your own sections and fields (rating, text, number, radio, dropdown, photo, signature).</p>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
