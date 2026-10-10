<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Settings</h1><div class="sub">AI providers for auto-filling vehicle data.</div></div>
</div>

<form method="post" action="<?= site_url('settings') ?>" enctype="multipart/form-data" x-data="{ p: '<?= esc($ai['provider']) ?>' }">
  <?= csrf_field() ?>

  <div class="card" style="margin-bottom:16px">
    <div class="card-h"><h3>Features</h3></div>
    <div class="card-b">
      <p class="sub" style="font-size:12.5px;color:var(--ink-3);margin:0 0 4px">
        Switch capabilities on or off individually.
      </p>
      <?php
        $rows = [
          ['offline', 'Offline capture (PWA)', 'Install to the home screen; capture with no signal and auto-sync when back online.', 'Phase'],
          ['vinDecode', 'VIN auto-decode', 'Decode a VIN into make/model/body/engine via free NHTSA data.', 'Live'],
          ['clientPortal', 'Client approval & e-sign', 'Customers view, sign and approve the report online.', 'Phase'],
          ['quotes', 'Repair quote builder', 'Turn cost-to-approve items into a priced estimate PDF.', 'Phase'],
          ['notifications', 'Automated report delivery', 'Auto-send the report link to the client on completion.', 'Phase', ['notifyEmail' => 'Email', 'notifyWhatsapp' => 'WhatsApp']],
          ['history', 'Re-inspection history & trends', 'Track a vehicle across inspections with wear trends over time.', 'Phase'],
          ['aiIntelligence', 'AI report intelligence', 'AI-written conclusions, finding triage and photo analysis.', 'Phase', ['aiConclusion' => 'Auto-write conclusion', 'aiPhotoAnalysis' => 'Analyze photos']],
          ['annotation', 'Photo annotation', 'Draw arrows / circles / notes directly on damage photos.', 'Phase'],
          ['analytics', 'Analytics dashboard', 'Workshop KPIs — common failures, scores, productivity.', 'Phase'],
          ['whiteLabel', 'White-label & payments', 'Per-workshop branding and online payments.', 'Phase', ['payments' => 'Payments']],
          ['vinCheck', 'VIN History Check', 'Run a VIN against free decode + paid accident/stolen/service providers, archive it, and share by PDF/email/QR.', 'Live'],
        ];
        foreach ($rows as $r):
          [$key, $title, $desc, $stage] = $r; $subs = $r[4] ?? [];
      ?>
        <div style="border-top:1px solid var(--line);padding:12px 0">
          <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer">
            <input type="checkbox" name="feat_<?= $key ?>" value="1" <?= $feat[$key]?'checked':'' ?> style="margin-top:3px">
            <span style="flex:1">
              <b style="font-size:14px"><?= esc($title) ?></b>
              <span class="sub" style="display:block;font-size:12px;color:var(--ink-3);margin-top:2px"><?= esc($desc) ?></span>
              <?php if ($subs): ?>
                <span style="display:flex;gap:18px;flex-wrap:wrap;margin-top:7px">
                  <?php foreach ($subs as $sk => $sl): ?>
                    <label style="display:flex;gap:6px;align-items:center;font-size:12.5px;color:var(--ink-2)">
                      <input type="checkbox" name="feat_<?= $sk ?>" value="1" <?= $feat[$sk]?'checked':'' ?>> <?= esc($sl) ?>
                    </label>
                  <?php endforeach; ?>
                </span>
              <?php endif; ?>
            </span>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="card-h"><h3>AI Assistant</h3></div>
    <div class="card-b grid" style="gap:16px">
      <label style="display:flex;gap:8px;align-items:center;font-size:14px;font-weight:600">
        <input type="checkbox" name="enabled" value="1" <?= $ai['enabled']?'checked':'' ?>> Enable AI features
      </label>

      <label style="display:flex;gap:8px;align-items:flex-start;font-size:14px;font-weight:600">
        <input type="checkbox" name="showVehicleImage" value="1" <?= $ai['showVehicleImage']?'checked':'' ?> style="margin-top:3px">
        <span>Show reference vehicle image
          <span class="sub" style="display:block;font-weight:400;font-size:11.5px;color:var(--ink-3)">A clean "ideal condition" photo of the model from Wikimedia (free, no key). Shown on the vehicle profile, capture form and PDF page 1 — alongside your real inspection photos.</span>
        </span>
      </label>

      <label class="fld"><span>Active provider</span>
        <select name="provider" x-model="p">
          <option value="claude" <?= $ai['provider']==='claude'?'selected':'' ?>>Claude (Anthropic)</option>
          <option value="openai" <?= $ai['provider']==='openai'?'selected':'' ?>>ChatGPT (OpenAI)</option>
        </select>
      </label>
    </div>
  </div>

  <div class="grid cols-2">
    <div class="card" :style="p==='claude' && 'box-shadow:0 0 0 2px var(--brand)'">
      <div class="card-h"><h3>Claude (Anthropic)</h3></div>
      <div class="card-b grid" style="gap:14px">
        <label class="fld"><span>API Key</span>
          <input type="password" name="claudeKey" autocomplete="off" placeholder="<?= $ai['claudeKey'] ? '•••••••• (leave blank to keep)' : 'sk-ant-...' ?>">
        </label>
        <label class="fld"><span>Model</span><input type="text" name="claudeModel" value="<?= esc($ai['claudeModel']) ?>" class="mono"></label>
        <div class="sub" style="font-size:11.5px;color:var(--ink-3)">Get a key at console.anthropic.com. Default model: <span class="mono">claude-opus-4-8</span>.</div>
      </div>
    </div>

    <div class="card" :style="p==='openai' && 'box-shadow:0 0 0 2px var(--brand)'">
      <div class="card-h"><h3>ChatGPT (OpenAI)</h3></div>
      <div class="card-b grid" style="gap:14px">
        <label class="fld"><span>API Key</span>
          <input type="password" name="openaiKey" autocomplete="off" placeholder="<?= $ai['openaiKey'] ? '•••••••• (leave blank to keep)' : 'sk-...' ?>">
        </label>
        <label class="fld"><span>Model</span><input type="text" name="openaiModel" value="<?= esc($ai['openaiModel']) ?>" class="mono"></label>
        <div class="sub" style="font-size:11.5px;color:var(--ink-3)">Get a key at platform.openai.com. Default model: <span class="mono">gpt-4o</span>.</div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:16px" x-data="{ proto: '<?= esc($notify['protocol']) ?>' }">
    <div class="card-h"><h3>Notifications (report delivery)</h3></div>
    <div class="card-b grid" style="gap:14px">
      <div class="grid cols-2" style="gap:14px">
        <label class="fld"><span>From email</span><input type="text" name="n_fromEmail" value="<?= esc($notify['fromEmail']) ?>" placeholder="reports@yourdomain.co.za"></label>
        <label class="fld"><span>From name</span><input type="text" name="n_fromName" value="<?= esc($notify['fromName']) ?>"></label>
      </div>
      <label class="fld"><span>Sending method</span>
        <select name="n_protocol" x-model="proto">
          <option value="mail" <?= $notify['protocol']==='mail'?'selected':'' ?>>Server mail (cPanel — simplest)</option>
          <option value="smtp" <?= $notify['protocol']==='smtp'?'selected':'' ?>>SMTP (recommended for deliverability)</option>
        </select>
      </label>
      <div class="grid cols-2" style="gap:14px" x-show="proto==='smtp'">
        <label class="fld"><span>SMTP host</span><input type="text" name="n_smtpHost" value="<?= esc($notify['smtpHost']) ?>" placeholder="mail.yourdomain.co.za"></label>
        <label class="fld"><span>SMTP username</span><input type="text" name="n_smtpUser" value="<?= esc($notify['smtpUser']) ?>" autocomplete="off"></label>
        <label class="fld"><span>SMTP password</span><input type="password" name="n_smtpPass" autocomplete="off" placeholder="<?= $notify['smtpPass'] ? '•••••••• (leave blank to keep)' : '' ?>"></label>
        <div class="grid cols-2" style="gap:10px">
          <label class="fld"><span>Port</span><input type="number" name="n_smtpPort" value="<?= esc($notify['smtpPort']) ?>"></label>
          <label class="fld"><span>Encryption</span>
            <select name="n_smtpCrypto">
              <option value="tls" <?= $notify['smtpCrypto']==='tls'?'selected':'' ?>>TLS</option>
              <option value="ssl" <?= $notify['smtpCrypto']==='ssl'?'selected':'' ?>>SSL</option>
              <option value="" <?= $notify['smtpCrypto']===''?'selected':'' ?>>None</option>
            </select>
          </label>
        </div>
      </div>

      <label style="display:flex;gap:8px;align-items:center;font-size:13px;font-weight:600">
        <input type="checkbox" name="n_notifyWorkshop" value="1" <?= $notify['notifyWorkshop']?'checked':'' ?>> Also copy the workshop on each send
      </label>
      <label class="fld" style="max-width:340px"><span>Workshop email (for copies)</span><input type="text" name="n_workshopEmail" value="<?= esc($notify['workshopEmail']) ?>"></label>

      <div style="border-top:1px solid var(--line);padding-top:14px">
        <div style="font-weight:700;font-size:13px;margin-bottom:8px">WhatsApp <span style="font-size:10px;font-weight:700;padding:1px 7px;border-radius:999px;background:#eef1f3;color:var(--ink-3)">OPTIONAL</span></div>
        <p class="sub" style="font-size:11.5px;color:var(--ink-3);margin:0 0 10px">Leave blank for now. When you set up a Meta WhatsApp Cloud API number, paste the token + phone-number ID here and tick the WhatsApp channel under Features. Business-initiated messages need an approved template.</p>
        <div class="grid cols-2" style="gap:14px">
          <label class="fld"><span>WhatsApp token</span><input type="password" name="n_waToken" autocomplete="off" placeholder="<?= $notify['waToken'] ? '•••••••• (leave blank to keep)' : 'EAAG…' ?>"></label>
          <label class="fld"><span>Phone number ID</span><input type="text" name="n_waPhoneId" value="<?= esc($notify['waPhoneId']) ?>" class="mono"></label>
        </div>
        <label class="fld" style="margin-top:12px"><span>Push (FCM) server key <span style="font-size:10px;color:var(--ink-3)">— for the mobile app</span></span>
          <input type="password" name="n_fcmKey" autocomplete="off" placeholder="<?= $notify['fcmServerKey'] ? '•••••••• (leave blank to keep)' : 'AAAA… (from Firebase)' ?>">
        </label>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-h"><h3>Branding (white-label)</h3></div>
    <div class="card-b grid" style="gap:14px">
      <p class="sub" style="font-size:11.5px;color:var(--ink-3);margin:0">Applies across the app, client portal and emails when the <b>White-label</b> feature is enabled.</p>
      <div class="grid cols-2" style="gap:14px">
        <label class="fld"><span>Brand name</span><input type="text" name="b_brandName" value="<?= esc($brand['brandName']) ?>"></label>
        <label class="fld"><span>Tagline</span><input type="text" name="b_tagline" value="<?= esc($brand['tagline']) ?>"></label>
        <label class="fld"><span>Primary colour</span><input type="color" name="b_primaryColor" value="<?= esc($brand['primaryColor']) ?>" style="height:44px;padding:4px"></label>
        <label class="fld"><span>Dark / text colour</span><input type="color" name="b_darkColor" value="<?= esc($brand['darkColor']) ?>" style="height:44px;padding:4px"></label>
      </div>
      <label class="fld"><span>Logo <?= $brand['logoPath'] ? '(replace)' : '' ?></span><input type="file" name="b_logo" accept="image/*"></label>
      <?php if ($brand['logoPath']): ?><div><img src="<?= base_url($brand['logoPath']) ?>" alt="logo" style="height:44px"></div><?php endif; ?>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-h"><h3>Payments (Paystack)</h3></div>
    <div class="card-b grid" style="gap:14px">
      <p class="sub" style="font-size:11.5px;color:var(--ink-3);margin:0">Lets clients pay from the portal when the <b>Payments</b> feature is on. Charges the approved repair total, or a flat inspection fee. Leave blank to keep it dormant.</p>
      <div class="grid cols-2" style="gap:14px">
        <label class="fld"><span>Public key</span><input type="text" name="p_paystackPublic" value="<?= esc($pay['paystackPublic']) ?>" class="mono" placeholder="pk_..."></label>
        <label class="fld"><span>Secret key</span><input type="password" name="p_paystackSecret" autocomplete="off" placeholder="<?= $pay['paystackSecret'] ? '•••••••• (leave blank to keep)' : 'sk_...' ?>"></label>
        <label class="fld"><span>Currency</span><input type="text" name="p_currency" value="<?= esc($pay['currency']) ?>" class="mono"></label>
        <label class="fld"><span>Flat inspection fee (0 = off)</span><input type="number" step="0.01" name="p_inspectionFee" value="<?= esc($pay['inspectionFee']) ?>"></label>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <div class="card-h"><h3>VIN History Check — data sources</h3></div>
    <div class="card-b grid" style="gap:16px">
      <p class="sub" style="font-size:11.5px;color:var(--ink-3);margin:0">
        Used when the <b>VIN History Check</b> feature is on. The FREE source decodes VIN specs at no cost.
        Each PAID slot is a named, swappable provider for history data the free source can't give you (accident,
        stolen status, title/registry). Enable any combination — a check runs every enabled provider and merges
        the results into one report. Leave a slot's key blank to keep it dormant.
      </p>

      <label style="display:flex;gap:8px;align-items:center;font-size:14px;font-weight:600;border-top:1px solid var(--line);padding-top:12px">
        <input type="checkbox" name="vin_freeEnabled" value="1" <?= $vinProviders['freeEnabled']?'checked':'' ?>>
        FREE — NHTSA vPIC VIN decode
      </label>
      <p class="sub" style="font-size:11.5px;color:var(--ink-3);margin:-8px 0 0">
        No key needed. Decodes make/model/year/body/engine. US registry — strong for globally-shared VIN patterns,
        thinner for SA-only derivatives. Never returns accident/stolen/service history.
      </p>

      <?php
        $slots = [
          ['p1', 'p1Label', 'VinAudit — accident, theft, title/salvage (US NMVTIS-backed)'],
          ['p2', 'p2Label', 'CarsXE — history events, theft, specs'],
          ['p3', 'p3Label', 'Didit NATIS (South Africa) — registry match for SA VINs; not an accident/theft report'],
        ];
        foreach ($slots as [$slot, $labelKey, $hint]):
      ?>
        <div style="border-top:1px solid var(--line);padding-top:12px">
          <label style="display:flex;gap:8px;align-items:center;font-size:14px;font-weight:600">
            <input type="checkbox" name="vin_<?= $slot ?>Enabled" value="1" <?= $vinProviders["{$slot}Enabled"]?'checked':'' ?>>
            PAID — <?= esc($vinProviders[$labelKey]) ?>
          </label>
          <p class="sub" style="font-size:11.5px;color:var(--ink-3);margin:4px 0 8px"><?= esc($hint) ?></p>
          <div class="grid cols-2" style="gap:14px">
            <label class="fld"><span>Display name</span><input type="text" name="vin_<?= $slot ?>Label" value="<?= esc($vinProviders[$labelKey]) ?>"></label>
            <label class="fld"><span>API key</span><input type="password" name="vin_<?= $slot ?>ApiKey" autocomplete="off" placeholder="<?= $vinProviders["{$slot}ApiKey"] ? '•••••••• (leave blank to keep)' : 'paste key...' ?>"></label>
            <label class="fld" style="grid-column:1/-1"><span>API base URL</span><input type="text" name="vin_<?= $slot ?>BaseUrl" value="<?= esc($vinProviders["{$slot}BaseUrl"]) ?>" class="mono"></label>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="margin-top:16px;display:flex;gap:10px">
    <button class="btn" type="submit">Save Settings</button>
  </div>
  <p class="sub" style="margin-top:12px;font-size:12px;color:var(--ink-3)">
    Keys are stored in your database and never shown again once saved. Leave a key field blank to keep the existing one.
  </p>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?= $this->endSection() ?>
