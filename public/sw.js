/* CheckA+ service worker — conservative offline shell.
   Only ever touches same-origin GET requests; never POST, never /media uploads. */
const CACHE = 'checka-v1';
const OFFLINE = '/offline.html';

self.addEventListener('install', (e) => {
  self.skipWaiting();
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll([OFFLINE]).catch(() => {})));
});

self.addEventListener('activate', (e) => {
  e.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)));
    await self.clients.claim();
  })());
});

self.addEventListener('message', (e) => {
  if (e.data === 'skipWaiting') self.skipWaiting();
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;                    // never interfere with saves/uploads
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;          // let fonts/CDN pass straight through
  if (url.pathname.startsWith('/media/')) return;      // don't cache user photos/videos

  // Page navigations: network-first, fall back to cache, then the offline page.
  if (req.mode === 'navigate') {
    e.respondWith((async () => {
      try {
        const net = await fetch(req);
        const c = await caches.open(CACHE);
        c.put(req, net.clone());
        return net;
      } catch (_) {
        return (await caches.match(req)) || (await caches.match(OFFLINE));
      }
    })());
    return;
  }

  // Static assets: cache-first (fast + offline).
  if (url.pathname.startsWith('/assets/')) {
    e.respondWith((async () => {
      const cached = await caches.match(req);
      if (cached) return cached;
      try {
        const net = await fetch(req);
        const c = await caches.open(CACHE);
        c.put(req, net.clone());
        return net;
      } catch (_) {
        return cached;
      }
    })());
  }
});
