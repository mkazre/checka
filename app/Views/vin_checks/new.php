<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Run VIN Check</h1><div class="sub">Runs every enabled provider (Settings → VIN History Check) and saves the combined result to the archive.</div></div>
</div>

<div class="card" style="max-width:560px">
  <div class="card-b">
    <form method="post" action="<?= site_url('vin-checks') ?>" class="grid" style="gap:14px">
      <?= csrf_field() ?>
      <?php if ($vehicle): ?><input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['id'] ?>"><?php endif; ?>
      <label class="fld">
        <span>VIN (17 characters)</span>
        <input type="text" name="vin" value="<?= esc($vin ?: ($vehicle['vin'] ?? '')) ?>" class="mono" maxlength="17" minlength="11" required autocapitalize="characters" style="text-transform:uppercase">
      </label>
      <?php if ($vehicle): ?>
        <p class="sub" style="font-size:12.5px;color:var(--ink-3);margin:0">
          Linked to <?= esc(trim(($vehicle['year']?:'').' '.($vehicle['make']?:'').' '.($vehicle['model']?:'')) ?: 'this vehicle') ?>.
        </p>
      <?php endif; ?>
      <div><button class="btn" type="submit">Run Check</button></div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
