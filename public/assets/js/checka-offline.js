/* CheckA+ offline queue — stores failed photo uploads / saves in IndexedDB and
   replays them when connectivity returns. Foreground sync (works on iOS Safari,
   which has no Background Sync). Exposes window.Checka. */
(function () {
  const BASE = (window.CHECKA_BASE || '/');
  const DB_NAME = 'checka-offline';
  const STORE = 'queue';
  const MAX_ATTEMPTS = 6;

  // ---- IndexedDB helpers ----
  function db() {
    return new Promise((res, rej) => {
      const r = indexedDB.open(DB_NAME, 1);
      r.onupgradeneeded = () => { r.result.createObjectStore(STORE, { keyPath: 'id', autoIncrement: true }); };
      r.onsuccess = () => res(r.result);
      r.onerror = () => rej(r.error);
    });
  }
  async function tx(mode) { return (await db()).transaction(STORE, mode).objectStore(STORE); }
  function reqP(r) { return new Promise((res, rej) => { r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error); }); }

  async function add(entry) { const s = await tx('readwrite'); return reqP(s.add(Object.assign({ attempts: 0, ts: Date.now() }, entry))); }
  async function all() { const s = await tx('readonly'); return reqP(s.getAll()); }
  async function del(id) { const s = await tx('readwrite'); return reqP(s.delete(id)); }
  async function put(entry) { const s = await tx('readwrite'); return reqP(s.put(entry)); }
  async function count() { try { const s = await tx('readonly'); return await reqP(s.count()); } catch (_) { return 0; } }

  // ---- Status banner ----
  let bar;
  function ensureBar() {
    if (bar) return bar;
    bar = document.createElement('div');
    bar.id = 'checka-net';
    bar.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:9999;padding:9px 14px;font:600 13px system-ui,sans-serif;text-align:center;color:#fff;display:none;transition:transform .2s';
    document.body.appendChild(bar);
    return bar;
  }
  async function refreshBar() {
    const b = ensureBar();
    const n = await count();
    document.dispatchEvent(new CustomEvent('checka:pending', { detail: n }));
    if (!navigator.onLine) {
      b.style.background = '#2d2d2d';
      b.style.display = 'block';
      b.textContent = n > 0 ? `Offline · ${n} change${n > 1 ? 's' : ''} will sync when you reconnect` : 'Offline · working locally';
    } else if (n > 0) {
      b.style.background = '#37b34a';
      b.style.display = 'block';
      b.textContent = `Syncing ${n} item${n > 1 ? 's' : ''}…`;
    } else {
      b.style.display = 'none';
    }
  }

  // ---- Sync ----
  let flushing = false;
  async function freshToken() {
    try { return await fetch(BASE + 'csrf', { credentials: 'same-origin' }).then((r) => r.json()); }
    catch (_) { return null; }
  }
  async function flush() {
    if (flushing || !navigator.onLine) return;
    flushing = true;
    try {
      const items = (await all()).sort((a, b) => a.id - b.id);
      for (const it of items) {
        const tok = await freshToken();
        const fd = new FormData();
        (it.fields || []).forEach(([k, v]) => fd.append(k, v));
        (it.files || []).forEach((f) => fd.append(f.name, f.blob, f.filename));
        if (tok && tok.name) fd.append(tok.name, tok.hash);
        let res;
        try {
          res = await fetch(it.url, { method: 'POST', body: fd, credentials: 'same-origin', headers: Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, tok && tok.name ? { [tok.name]: tok.hash } : {}) });
        } catch (_) {
          break; // still offline — stop, keep the queue
        }
        if (res.ok) {
          await del(it.id);
          await refreshBar();
        } else {
          it.attempts = (it.attempts || 0) + 1;
          if (it.attempts >= MAX_ATTEMPTS) { await del(it.id); } else { await put(it); }
        }
      }
    } finally {
      flushing = false;
      await refreshBar();
    }
  }

  // ---- Public API ----
  window.Checka = {
    async enqueue(entry) { await add(entry); await refreshBar(); if (navigator.onLine) flush(); },
    flush,
    count,
    isOnline: () => navigator.onLine,
  };

  window.addEventListener('online', () => { refreshBar(); flush(); });
  window.addEventListener('offline', refreshBar);
  document.addEventListener('DOMContentLoaded', () => { refreshBar(); if (navigator.onLine) flush(); });
})();
