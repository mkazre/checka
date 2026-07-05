<?= $this->extend('layouts/app') ?>
<?= $this->section('head') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
  .sheet{max-width:960px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden}
  .cr-head{display:flex;align-items:center;gap:18px;padding:18px 24px;border-bottom:1px solid var(--line)}
  .cr-head .mark{width:44px;height:44px;border-radius:9px;background:var(--brand);display:grid;place-items:center;flex:none}
  .cr-head .mark svg{width:26px;height:26px}
  .cr-head .ttl{font-family:var(--display);font-weight:700;font-size:18px;letter-spacing:.02em}
  .cr-head .ttl small{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.24em;color:var(--ink-3);text-transform:uppercase;font-weight:400}
  .cr-head .rep{margin-left:auto;text-align:right;font-family:var(--mono);font-size:10.5px;color:var(--ink-2);line-height:1.6}
  .cr-head .rep b{color:var(--ink)}
  #pqr{width:74px;height:74px}
  .cr-sub{display:flex;justify-content:space-between;gap:20px;padding:10px 24px;background:#fafbfc;border-bottom:1px solid var(--line);font-size:12.5px}
  .cr-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;padding:14px 24px;border-bottom:1px solid var(--line)}
  .cr-strip .ph{aspect-ratio:4/3;border-radius:7px;overflow:hidden;border:1px solid var(--line);background:#eef1f3;display:grid;place-items:center}
  .cr-strip .ph img{width:100%;height:100%;object-fit:cover}
  .cr-strip .ph span{font-family:var(--mono);font-size:9px;color:var(--ink-3)}
  .cr-sec{padding:6px 24px 16px}
  .cr-sec .bar{display:flex;align-items:center;gap:10px;margin:14px 0 10px}
  .cr-sec .bar h3{font-family:var(--display);font-size:14px;font-weight:700;color:var(--brand-ink)}
  .cr-sec .bar::after{content:"";flex:1;height:2px;background:var(--brand);opacity:.25;border-radius:2px}
  .cr-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 34px}
  .cr-row{display:grid;grid-template-columns:150px 1fr;align-items:center;gap:10px;padding:5px 0;border-bottom:1px dotted var(--line)}
  .cr-row label{font-size:11.5px;color:var(--ink-3);font-weight:600}
  .cr-row input,.cr-row select{border:none;border-bottom:1px solid transparent;padding:5px 4px;font-size:13.5px;font-weight:600;background:transparent;border-radius:0}
  .cr-row input:hover,.cr-row select:hover{border-bottom-color:var(--line)}
  .cr-row input:focus,.cr-row select:focus{outline:none;border-bottom:1px solid var(--brand);background:#f7fbfc}
  @media(max-width:760px){
    .cr-grid{grid-template-columns:1fr}
    .cr-strip{grid-template-columns:repeat(3,1fr)}
    .cr-head{flex-wrap:wrap}
    .cr-head .rep{margin-left:0;text-align:left;width:100%}
    .cr-sub{flex-wrap:wrap;gap:6px}
    .cr-sub div{text-align:left !important}
    .cr-sec{padding:6px 16px 14px}
  }
  @media(max-width:480px){
    .cr-strip{grid-template-columns:repeat(2,1fr)}
    .cr-row{grid-template-columns:1fr;gap:2px;padding:7px 0}
    .cr-row label{color:var(--ink-3)}
  }
  .dmg{width:100%;border-collapse:collapse}
  .dmg-scroll{overflow-x:auto}
  .dmg th{text-align:left;font-family:var(--mono);font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);padding:6px 8px;border-bottom:1px solid var(--line)}
  .dmg td{padding:7px 8px;border-bottom:1px solid var(--line);font-size:13px}
  .dmg .dmg-name{color:var(--major);font-weight:600}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
  $v = $vehicle;
  $val = fn($k) => esc($v[$k] ?? '');
  $insDate = $inspection['inspection_date'] ?? '';
?>

<div class="page-head">
  <div><h1>Vehicle Profile</h1><div class="sub mono"><?= esc(trim(($v['year']?:'').' '.($v['make']?:'').' '.($v['model']?:'')) ?: 'Vehicle') ?></div></div>
  <div class="spacer"></div>
  <a class="btn ghost" href="<?= site_url('vehicles/scan') ?>">Scan Disc</a>
  <?php if ($inspection): ?><a class="btn ghost" href="<?= site_url('reports/'.$inspection['report_uid']) ?>">Latest Report</a><?php endif; ?>
  <a class="btn" href="<?= site_url('inspections/new') ?>">New Inspection</a>
</div>

<form method="post" action="<?= site_url('vehicles/'.$v['id']) ?>">
  <?= csrf_field() ?>
  <div class="sheet">

    <!-- Header -->
    <div class="cr-head">
      <img src="<?= base_url('assets/img/logo.png') ?>" alt="CheckA+" style="height:42px;width:auto">
      <div class="ttl">Vehicle Condition Profile<small>CheckA+ by WheelsTurn</small></div>
      <div class="rep">
        <div id="pqr"></div>
        <div>VIN · <b><?= $val('vin') ?: '—' ?></b></div>
      </div>
    </div>

    <!-- Sub header -->
    <div class="cr-sub">
      <div><span style="color:var(--ink-3)">Client</span> &nbsp;<b><?= $val('client') ?: '—' ?></b></div>
      <div style="text-align:right;color:var(--ink-3)">Inspection date &nbsp;<b style="color:var(--ink)"><?= esc($insDate ?: '—') ?></b></div>
    </div>

    <!-- Photo strip -->
    <div class="cr-strip">
      <?php for ($i = 0; $i < 5; $i++): $p = $photos[$i] ?? null; ?>
        <div class="ph"><?php if ($p): ?><img src="<?= $p['thumb'] ?>" alt=""><?php else: ?><span>—</span><?php endif; ?></div>
      <?php endfor; ?>
    </div>

    <!-- Assignment -->
    <div class="cr-sec">
      <div class="bar"><h3>Assignment</h3></div>
      <div class="cr-grid">
        <div class="cr-row"><label>Client</label><input name="client" value="<?= $val('client') ?>"></div>
        <div class="cr-row"><label>Inspection Location</label><input name="inspection_location" value="<?= $val('inspection_location') ?>"></div>
      </div>
    </div>

    <!-- Vehicle Data -->
    <div class="cr-sec">
      <div class="bar"><h3>Vehicle Data</h3></div>
      <div class="cr-grid">
        <!-- left column -->
        <div>
          <div class="cr-row"><label>Vehicle Type</label><input name="vehicle_type" value="<?= $val('vehicle_type') ?>"></div>
          <div class="cr-row"><label>Manufacturer</label><input name="make" value="<?= $val('make') ?>"></div>
          <div class="cr-row"><label>Model</label><input name="model" value="<?= $val('model') ?>"></div>
          <div class="cr-row"><label>Derivative</label><input name="derivative" value="<?= $val('derivative') ?>"></div>
          <div class="cr-row"><label>VIN</label><input name="vin" class="mono" value="<?= $val('vin') ?>"></div>
          <div class="cr-row"><label>Engine Number</label><input name="engine_number" class="mono" value="<?= $val('engine_number') ?>"></div>
          <div class="cr-row"><label>Doors</label><input name="doors" value="<?= $val('doors') ?>"></div>
          <div class="cr-row"><label>Color</label><input name="colour" value="<?= $val('colour') ?>"></div>
          <div class="cr-row"><label>Year Model</label><input name="year" value="<?= $val('year') ?>"></div>
          <div class="cr-row"><label>Km Reading</label><input name="odometer" value="<?= $val('odometer') ?>"></div>
          <div class="cr-row"><label>Cab / Body Type</label><input name="body_type" value="<?= $val('body_type') ?>"></div>
        </div>
        <!-- right column -->
        <div>
          <div class="cr-row"><label>Registered Owner</label><input name="registered_owner" value="<?= $val('registered_owner') ?>"></div>
          <div class="cr-row"><label>Registration No.</label><input name="plate" value="<?= $val('plate') ?>"></div>
          <div class="cr-row"><label>Register Number</label><input name="register_number" value="<?= $val('register_number') ?>"></div>
          <div class="cr-row"><label>Licence No.</label><input name="licence_no" value="<?= $val('licence_no') ?>"></div>
          <div class="cr-row"><label>Date First Licensing</label><input name="date_first_licensing" value="<?= $val('date_first_licensing') ?>"></div>
          <div class="cr-row"><label>Last COR</label><input name="last_cor" value="<?= $val('last_cor') ?>"></div>
          <div class="cr-row"><label>Licence Expiry</label><input name="licence_expiry" value="<?= $val('licence_expiry') ?>"></div>
          <div class="cr-row"><label>Power / Displacement</label><input name="power_displacement" value="<?= $val('power_displacement') ?>"></div>
          <div class="cr-row"><label>Paint Finish</label><input name="paint_finish" value="<?= $val('paint_finish') ?>"></div>
          <div class="cr-row"><label>Fuel</label><input name="fuel" value="<?= $val('fuel') ?>"></div>
          <div class="cr-row"><label>Transmission</label><input name="transmission" value="<?= $val('transmission') ?>"></div>
        </div>
      </div>
      <div class="cr-grid" style="margin-top:6px">
        <div class="cr-row"><label>GVM</label><input name="gvm" value="<?= $val('gvm') ?>"></div>
        <div class="cr-row"><label>Tare</label><input name="tare" value="<?= $val('tare') ?>"></div>
        <div class="cr-row"><label>Seating</label><input name="seating" value="<?= $val('seating') ?>"></div>
        <div class="cr-row"><label>Date of Test</label><input name="date_of_test" value="<?= $val('date_of_test') ?>"></div>
      </div>
    </div>

    <!-- Options -->
    <div class="cr-sec">
      <div class="bar"><h3>Vehicle Options &amp; Accessories</h3></div>
      <textarea name="options" rows="3" placeholder="e.g. ABS; Airbags; Alloy wheels; Cruise control; …" style="font-size:13px"><?= $val('options') ?></textarea>
    </div>

    <!-- Damage (from latest inspection) -->
    <div class="cr-sec">
      <div class="bar"><h3>Damage &amp; Findings</h3></div>
      <?php if (empty($findings)): ?>
        <div class="sub" style="font-size:12.5px;color:var(--ink-3)">No findings yet. <?= $inspection ? 'The latest inspection is all-clear.' : 'Run an inspection to populate this.' ?></div>
      <?php else: ?>
        <div class="dmg-scroll">
        <table class="dmg" style="min-width:420px">
          <thead><tr><th style="width:14%">Ref</th><th style="width:46%">Damage</th><th>Repair Way / Comment</th></tr></thead>
          <tbody>
          <?php foreach ($findings as $f): ?>
            <tr>
              <td class="mono" style="color:var(--ink-3)"><?= esc($f['ref']) ?></td>
              <td class="dmg-name"><?= esc($f['name']) ?></td>
              <td style="color:var(--ink-2)"><?= esc($f['comment'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <div class="sub" style="font-size:11.5px;color:var(--ink-3);margin-top:8px">Auto-compiled from the latest inspection<?= $inspection ? ' ('.esc($insDate).')' : '' ?>.</div>
      <?php endif; ?>
    </div>

    <div style="padding:16px 24px;border-top:1px solid var(--line);display:flex;gap:10px">
      <button class="btn" type="submit">Save Profile</button>
      <a class="btn ghost" href="<?= site_url('vehicles') ?>">Back to vehicles</a>
    </div>
  </div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
  try { new QRCode(document.getElementById("pqr"), {text:"<?= esc($v['vin'] ?: site_url('vehicles/'.$v['id'].'/profile'), 'js') ?>", width:74, height:74, colorDark:"#12171d", colorLight:"#fff"}); } catch(e){}
</script>
<?= $this->endSection() ?>
