// ============================================================
//  AlfaHome Service Worker v3
//  Features: offline cache · push notifications · background sync
// ============================================================

const CACHE_VERSION = 'v3';

const STATIC_CACHE = 'alfahome-static-' + CACHE_VERSION;
const PAGE_CACHE   = 'alfahome-pages-'  + CACHE_VERSION;
const FONT_CACHE   = 'alfahome-fonts-'  + CACHE_VERSION;
const DATA_CACHE   = 'alfahome-data-'   + CACHE_VERSION; // API snapshots

const STATIC_ASSETS = [
  '/offline.html',
  '/favicon.png',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

// ── INSTALL ────────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// ── ACTIVATE ───────────────────────────────────────────────────────────────
self.addEventListener('activate', event => {
  const valid = [STATIC_CACHE, PAGE_CACHE, FONT_CACHE, DATA_CACHE];
  event.waitUntil(
    caches.keys()
      .then(keys =>
        Promise.all(
          keys.filter(k => !valid.includes(k)).map(k => caches.delete(k))
        )
      )
      .then(() => self.clients.claim())
  );
});

// ── FETCH ──────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // Never intercept auth / sensitive routes
  const blocked = ['/login', '/logout', '/register'];
  if (blocked.some(p => url.pathname.startsWith(p))) return;

  // ── Vite build assets (content-hashed → cache forever) ──────────────
  if (url.pathname.startsWith('/build/')) {
    event.respondWith(cacheFirst(req, STATIC_CACHE));
    return;
  }

  // ── Static public files ──────────────────────────────────────────────
  if (
    url.pathname === '/favicon.png' ||
    url.pathname.startsWith('/icons/') ||
    url.pathname.startsWith('/img/') ||
    url.pathname.startsWith('/alfa-home-logo')
  ) {
    event.respondWith(cacheFirst(req, STATIC_CACHE));
    return;
  }

  // ── CDN fonts / external scripts ─────────────────────────────────────
  if (
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com') ||
    url.hostname.includes('cdn.jsdelivr.net')
  ) {
    event.respondWith(cacheFirst(req, FONT_CACHE));
    return;
  }

  // ── API dashboard snapshot: stale-while-revalidate ───────────────────
  if (url.pathname === '/api/dashboard/snapshot') {
    event.respondWith(staleWhileRevalidate(req, DATA_CACHE));
    return;
  }

  // ── Other API calls: network only (never cache mutations or sensitive) ─
  if (url.pathname.startsWith('/api/')) return;

  // ── HTML navigation: network-first → cached page → offline fallback ──
  if (req.mode === 'navigate') {
    event.respondWith(networkFirstNav(req));
    return;
  }

  // ── Default: try network, fallback to cache ──────────────────────────
  event.respondWith(
    fetch(req).catch(() => caches.match(req))
  );
});

// ── PUSH NOTIFICATIONS ─────────────────────────────────────────────────────
self.addEventListener('push', event => {
  let payload = { title: 'AlfaHome', body: 'Nova notificação', url: '/dashboard' };

  if (event.data) {
    try { payload = { ...payload, ...event.data.json() }; }
    catch (e) { payload.body = event.data.text(); }
  }

  event.waitUntil(
    self.registration.showNotification(payload.title, {
      body:    payload.body,
      icon:    '/icons/icon-192.png',
      badge:   '/icons/icon-192.png',
      vibrate: [200, 100, 200],
      tag:     payload.tag || 'alfahome-notif',
      renotify: true,
      data:    { url: payload.url || '/dashboard' },
      actions: [
        { action: 'open',    title: 'Abrir'    },
        { action: 'dismiss', title: 'Ignorar'  },
      ],
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'dismiss') return;

  const targetUrl = event.notification.data?.url || '/dashboard';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(clients => {
        // Focus existing window if open
        const existing = clients.find(c => c.url.includes(self.location.origin));
        if (existing) {
          existing.focus();
          return existing.navigate(targetUrl);
        }
        return self.clients.openWindow(targetUrl);
      })
  );
});

// ── BACKGROUND SYNC ────────────────────────────────────────────────────────
self.addEventListener('sync', event => {
  if (event.tag === 'sync-despesas') {
    event.waitUntil(replayQueue('despesas', '/api/sync/despesa'));
  }
  if (event.tag === 'sync-receitas') {
    event.waitUntil(replayQueue('receitas', '/api/sync/receita'));
  }
});

async function replayQueue(storeName, endpoint) {
  const db  = await openIDB();
  const all = await idbGetAll(db, storeName);

  for (const item of all) {
    try {
      const res = await fetch(endpoint, {
        method:  'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include',
        body: JSON.stringify(item),
      });

      if (res.ok) {
        await idbDelete(db, storeName, item.client_queue_id);
        // Notify all open clients
        const clients = await self.clients.matchAll({ type: 'window' });
        clients.forEach(c => c.postMessage({
          type: 'sync-success',
          store: storeName,
          item: item,
        }));
      }
    } catch (err) {
      // Will retry on next sync event
      console.warn('[SW] Sync failed for', storeName, err);
    }
  }
}

// ── IndexedDB helpers (used by SW background sync) ─────────────────────────
const IDB_NAME    = 'alfahome-offline';
const IDB_VERSION = 1;

function openIDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(IDB_NAME, IDB_VERSION);

    req.onupgradeneeded = e => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains('despesas')) {
        db.createObjectStore('despesas', { keyPath: 'client_queue_id' });
      }
      if (!db.objectStoreNames.contains('receitas')) {
        db.createObjectStore('receitas', { keyPath: 'client_queue_id' });
      }
    };

    req.onsuccess = e => resolve(e.target.result);
    req.onerror   = e => reject(e.target.error);
  });
}

function idbGetAll(db, storeName) {
  return new Promise((resolve, reject) => {
    const tx  = db.transaction(storeName, 'readonly');
    const req = tx.objectStore(storeName).getAll();
    req.onsuccess = e => resolve(e.target.result);
    req.onerror   = e => reject(e.target.error);
  });
}

function idbDelete(db, storeName, key) {
  return new Promise((resolve, reject) => {
    const tx  = db.transaction(storeName, 'readwrite');
    const req = tx.objectStore(storeName).delete(key);
    req.onsuccess = () => resolve();
    req.onerror   = e => reject(e.target.error);
  });
}

// ── Cache strategy helpers ──────────────────────────────────────────────────

async function cacheFirst(req, cacheName) {
  const cached = await caches.match(req);
  if (cached) return cached;
  try {
    const res = await fetch(req);
    if (res.ok) (await caches.open(cacheName)).put(req, res.clone());
    return res;
  } catch {
    return new Response('', { status: 408 });
  }
}

async function staleWhileRevalidate(req, cacheName) {
  const cache  = await caches.open(cacheName);
  const cached = await cache.match(req);

  const fetchPromise = fetch(req).then(res => {
    if (res.ok) cache.put(req, res.clone());
    return res;
  }).catch(() => null);

  return cached || await fetchPromise || new Response('{}', {
    headers: { 'Content-Type': 'application/json' },
  });
}

async function networkFirstNav(req) {
  const cache = await caches.open(PAGE_CACHE);
  try {
    const res = await fetch(req);
    if (res.ok) cache.put(req, res.clone());
    return res;
  } catch {
    const cached = await cache.match(req);
    if (cached) return cached;
    return await caches.match('/offline.html') ||
      new Response(
        '<html><body style="font-family:system-ui;background:#0b1120;color:#e5e7eb;display:flex;align-items:center;justify-content:center;height:100vh;text-align:center"><div><h1>Sem conexão</h1><p>Tente novamente quando estiver online.</p></div></body></html>',
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
      );
  }
}
