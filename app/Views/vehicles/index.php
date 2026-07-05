<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Vehicles</h1><div class="sub">The vehicle register.</div></div>
</div>

<div class="grid cols-2">
  <div class="card">
    <div class="card-h"><h3>Register</h3></div>
    <?php if (empty($vehicles)): ?>
      <div class="empty">No vehicles yet.</div>
    <?php else: ?>
    <table class="tbl">
      <thead><tr><th>Vehicle</th><th>Reg</th><th>VIN</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($vehicles as $v): ?>
        <tr>
          <td><b><?= esc(trim(($v['year']?:'').' '.($v['make']?:'').' '.($v['model']?:'')) ?: 'Vehicle') ?></b><div class="sub" style="font-size:12px;color:var(--ink-3)"><?= esc($v['colour']) ?></div></td>
          <td class="mono"><?= esc($v['plate'] ?: '—') ?></td>
          <td class="mono" style="font-size:11px"><?= esc($v['vin'] ?: '—') ?></td>
          <td style="text-align:right">
            <form method="post" action="<?= site_url('vehicles/'.$v['id'].'/delete') ?>" onsubmit="return confirm('Delete vehicle?')"><?= csrf_field() ?><button class="btn danger sm">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-h"><h3>Add Vehicle</h3></div>
    <div class="card-b">
      <form method="post" action="<?= site_url('vehicles') ?>" class="grid cols-2" style="gap:12px">
        <?= csrf_field() ?>
        <label class="fld"><span>Make</span><input type="text" name="make" placeholder="Volkswagen"></label>
        <label class="fld"><span>Model</span><input type="text" name="model" placeholder="Polo"></label>
        <label class="fld"><span>Year</span><input type="number" name="year"></label>
        <label class="fld"><span>Colour</span><input type="text" name="colour"></label>
        <label class="fld"><span>Registration</span><input type="text" name="plate"></label>
        <label class="fld"><span>VIN</span><input type="text" name="vin" class="mono"></label>
        <label class="fld"><span>Odometer</span><input type="text" name="odometer"></label>
        <label class="fld"><span>Dealer / Seller</span><input type="text" name="seller"></label>
        <div style="grid-column:span 2"><button class="btn" type="submit">Add Vehicle</button></div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
