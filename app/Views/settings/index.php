<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="page-head">
  <div><h1>Settings</h1><div class="sub">AI providers for auto-filling vehicle data.</div></div>
</div>

<form method="post" action="<?= site_url('settings') ?>" x-data="{ p: '<?= esc($ai['provider']) ?>' }">
  <?= csrf_field() ?>

  <div class="card" style="margin-bottom:16px">
    <div class="card-h"><h3>Features</h3></div>
    <div class="card-b">
      <p class="sub" style="font-size:12.5px;color:var(--ink-3);margin:0 0 4px">
        Switch capabilities on or off individually. Some are live now; the rest are rolling out in phases — you can pre-enable them and they activate as each phase deploys.
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
        ];
        foreach ($rows as $r):
          [$key, $title, $desc, $stage] = $r; $subs = $r[4] ?? [];
      ?>
        <div style="border-top:1px solid var(--line);padding:12px 0">
          <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer">
            <input type="checkbox" name="feat_<?= $key ?>" value="1" <?= $feat[$key]?'checked':'' ?> style="margin-top:3px">
            <span style="flex:1">
              <b style="font-size:14px"><?= esc($title) ?></b>
              <span style="font-size:9.5px;font-weight:700;letter-spacing:.05em;padding:1px 7px;border-radius:999px;margin-left:6px;<?= $stage==='Live'?'background:var(--ok-bg);color:#0f5f2e':'background:#eef1f3;color:var(--ink-3)' ?>"><?= $stage==='Live'?'LIVE':'ROLLING OUT' ?></span>
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
