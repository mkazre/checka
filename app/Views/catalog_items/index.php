<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Items</h1><div class="sub">Every item you've ordered. They're suggested as you type on a purchase order.</div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('purchase-orders') ?>">Purchase Orders</a>
  <a class="btn" href="<?= site_url('items/new') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> New Item
  </a>
</div>

<div class="card">
  <div class="card-h" style="flex-wrap:wrap;gap:8px">
    <h3>Catalogue <span class="sub" style="font-weight:400;color:var(--ink-3)">(<?= count($items) ?>)</span></h3>
    <form method="get" action="<?= site_url('items') ?>" style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap">
      <select name="sort" onchange="this.form.submit()" style="width:auto;padding:7px 10px;font-size:13px">
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>A–Z</option>
        <option value="used" <?= $sort === 'used' ? 'selected' : '' ?>>Most used</option>
        <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Recently used</option>
      </select>
      <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Search name, part no…" style="min-width:220px;padding:7px 10px;font-size:13px">
    </form>
  </div>
  <?php if (empty($items)): ?>
    <div class="empty" style="padding:26px 18px"><?= $q !== '' ? 'No items match.' : 'No items yet — they\'re saved automatically the first time you use them on a purchase order.' ?></div>
  <?php else: ?>
  <div class="tbl-scroll">
  <table class="tbl">
    <thead><tr><th>Item</th><th>Part no</th><th style="text-align:right">Last price (excl.)</th><th style="text-align:right">Times used</th><th>Last used</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr>
        <td><b><?= esc($i['name']) ?></b><?= $i['notes'] ? '<div class="sub" style="font-size:12px;color:var(--ink-3)">' . esc($i['notes']) . '</div>' : '' ?></td>
        <td class="mono" style="font-size:12.5px"><?= esc($i['part_no'] ?: '—') ?></td>
        <td style="text-align:right"><?= $i['default_price'] !== null ? esc(money($i['default_price'])) : '<span class="sub" style="color:var(--ink-3)">—</span>' ?></td>
        <td style="text-align:right"><?= (int) $i['use_count'] ?></td>
        <td style="font-size:13px"><?= $i['last_used_at'] ? esc(substr($i['last_used_at'], 0, 10)) : '—' ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="<?= site_url('items/'.$i['id'].'/edit') ?>">Edit</a>
          <form method="post" action="<?= site_url('items/'.$i['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('Remove “<?= esc($i['name'], 'js') ?>” from the list? Existing purchase orders keep their lines.')"><?= csrf_field() ?><button class="btn danger sm">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>
