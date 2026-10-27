<?php
/**
 * Searchable client dropdown (Alpine.js — the page must load Alpine).
 * Type to filter by name / contact / email / phone / VAT no, or scroll.
 * Dispatches a `client-picked` window event with {id} on change.
 *
 * @var list<array<string,mixed>> $clients
 * @var int|null $selectedId
 * @var string   $name       input name (default client_id)
 * @var bool     $required
 * @var string   $returnTo   path to come back to after "Add a new client"
 */
$name     ??= 'client_id';
$required ??= true;
$returnTo ??= '';
$noun     ??= 'client';
$addUrl   ??= site_url('clients/new' . ($returnTo !== '' ? '?return=' . rawurlencode($returnTo) : ''));
$pid  = 'cp' . bin2hex(random_bytes(3));
$list = array_map(static fn ($c) => [
    'id'      => (int) $c['id'],
    'name'    => $c['name'],
    'type'    => (string) ($c['type'] ?? ''),
    'contact' => (string) ($c['contact_person'] ?? ''),
    'email'   => (string) ($c['email'] ?? ''),
    'phone'   => (string) ($c['phone'] ?? ''),
    'vat'     => (string) ($c['vat_number'] ?? ''),
], $clients);
?>
<script type="application/json" id="<?= $pid ?>-data"><?= json_encode($list, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<div class="cp" x-data="clientPicker('<?= $pid ?>', <?= (int) ($selectedId ?? 0) ?>, <?= $required ? 'true' : 'false' ?>)" @click.outside="open=false" @keydown.escape="open=false">
  <input type="hidden" name="<?= esc($name, 'attr') ?>" :value="selected ? selected.id : ''">
  <button type="button" class="cp-box" :class="{err: err}" @click="toggle()">
    <template x-if="selected">
      <span class="cp-sel">
        <b x-text="selected.name"></b>
        <span class="cp-tag" x-show="selected.type" x-text="selected.type === 'individual' ? 'Individual' : 'Business'"></span>
        <span class="cp-meta" x-text="[selected.contact, selected.phone, selected.email].filter(Boolean).join(' · ')"></span>
      </span>
    </template>
    <template x-if="!selected"><span class="cp-ph">Search or scroll to choose a <?= esc($noun) ?>…</span></template>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M6 9l6 6 6-6"/></svg>
  </button>
  <div class="cp-err" x-show="err" x-cloak>Choose a <?= esc($noun) ?> to continue.</div>
  <div class="cp-drop" x-show="open" x-cloak>
    <input x-ref="q" type="text" x-model="q" @input="hi=0" placeholder="Type a name, contact, email, phone or VAT no…"
           @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="filtered[hi] && pick(filtered[hi])">
    <div class="cp-list" x-ref="list">
      <template x-for="(c, i) in filtered" :key="c.id">
        <div class="cp-opt" :class="{active: i === hi, chosen: selected && selected.id === c.id}" @click="pick(c)" @mouseenter="hi = i">
          <div><b x-text="c.name"></b> <span class="cp-tag" x-show="c.type" x-text="c.type === 'individual' ? 'Individual' : 'Business'"></span></div>
          <div class="cp-meta" x-text="[c.contact, c.phone, c.email, c.vat ? 'VAT ' + c.vat : ''].filter(Boolean).join(' · ')"></div>
        </div>
      </template>
      <div class="cp-empty" x-show="!filtered.length">No <?= esc($noun) ?> matches “<span x-text="q"></span>”.</div>
    </div>
    <a class="cp-add" href="<?= esc($addUrl) ?>">+ Add a new <?= esc($noun) ?></a>
  </div>
</div>

<?php if (! defined('CHECKA_CLIENT_PICKER_JS')): define('CHECKA_CLIENT_PICKER_JS', true); ?>
<style>
  [x-cloak]{display:none!important}
  .cp{position:relative}
  .cp-box{width:100%;display:flex;align-items:center;gap:10px;justify-content:space-between;text-align:left;background:#fff;border:1px solid var(--line);border-radius:9px;padding:10px 12px;cursor:pointer;font:inherit;color:var(--ink);min-height:46px}
  .cp-box.err{border-color:var(--major);box-shadow:0 0 0 3px #b4231822}
  .cp-box svg{flex:none;color:var(--ink-3)}
  .cp-sel{display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 8px;min-width:0}
  .cp-ph{color:var(--ink-3)}
  .cp-tag{font-family:var(--mono);font-size:10px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);border:1px solid var(--line);border-radius:999px;padding:1px 7px}
  .cp-meta{font-size:12px;color:var(--ink-3);width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .cp-err{color:var(--major);font-size:12.5px;margin-top:6px;font-weight:600}
  .cp-drop{position:absolute;z-index:40;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid var(--line);border-radius:11px;box-shadow:0 14px 40px rgba(16,23,29,.14);padding:8px}
  .cp-list{max-height:280px;overflow-y:auto;margin-top:6px}
  .cp-opt{padding:9px 10px;border-radius:8px;cursor:pointer}
  .cp-opt.active{background:#2d2d2d0d}
  .cp-opt.chosen{box-shadow:inset 3px 0 0 var(--brand)}
  .cp-empty{padding:12px 10px;color:var(--ink-3);font-size:13px}
  .cp-add{display:block;border-top:1px solid var(--line);margin-top:6px;padding:9px 10px 3px;font-weight:600;font-size:13px}
</style>
<script>
  function clientPicker(pid, selectedId, required) {
    const all = JSON.parse(document.getElementById(pid + '-data').textContent || '[]');
    return {
      all, q: '', open: false, hi: 0, err: false,
      selected: all.find(c => c.id === selectedId) || null,
      get filtered() {
        const t = this.q.trim().toLowerCase();
        if (!t) return this.all;
        return this.all.filter(c => [c.name, c.contact, c.email, c.phone, c.vat].join(' ').toLowerCase().includes(t));
      },
      init() {
        const form = this.$el.closest('form');
        if (form && required) {
          form.addEventListener('submit', (e) => {
            if (!this.selected) { e.preventDefault(); this.err = true; this.$el.scrollIntoView({behavior: 'smooth', block: 'center'}); }
          });
        }
        if (this.selected) this.$nextTick(() => window.dispatchEvent(new CustomEvent('client-picked', {detail: {id: this.selected.id}})));
      },
      toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.$refs.q.focus()); },
      move(d) {
        const n = this.filtered.length; if (!n) return;
        this.hi = (this.hi + d + n) % n;
        this.$nextTick(() => { const el = this.$refs.list.querySelectorAll('.cp-opt')[this.hi]; el && el.scrollIntoView({block: 'nearest'}); });
      },
      pick(c) {
        this.selected = c; this.open = false; this.q = ''; this.err = false;
        window.dispatchEvent(new CustomEvent('client-picked', {detail: {id: c.id}}));
      },
    };
  }
</script>
<?php endif; ?>
