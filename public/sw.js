// AlfaHome Service Worker
// Bump CACHE_VERSION on every deploy to invalidate old caches
const CACHE_VERSION = 'v2';

const STATIC_CACHE = 'alfahome-static-' + CACHE_VERSION;
const PAGE_CACHE   = 'alfahome-pages-'  + CACHE_VERSION;
const FONT_CACHE   = 'alfahome-fonts-'  + CACHE_VERSION;

const STATIC_ASSETS = [
  '/offline.html',
  '/favicon.png',
  '/icons/icon-192.png',
  '/icons/icon-512.png'
];

// ── INSTALL: pre-cache core static assets ──────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// ── ACTIVATE: remove all caches from previous versions ─────────────────────
self.addEventListener('activate', event => {
  const valid = [STATIC_CACHE, PAGE_CACHE, FONT_CACHE];
  event.waitUntil(
    caches.keys()
      .then(keys =>
        Promise.all(
          keys
            .filter(key => !valid.includes(key))
            .map(key => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  );
});

// ── FETCH: route-based cache strategies ────────────────────────────────────
self.addEventListener('fetch', event => {
  const req = event.request;
  const url = new URL(req.url);

  // Never intercept non-GET (POST/PUT/DELETE — forms, CSRF, logout)
  if (req.method !== 'GET') return;

  // Never intercept sensitive API/auth routes
  const blocked = ['/login', '/logout', '/register', '/api/'];
  if (blocked.some(p => url.pathname.startsWith(p))) return;

  // ── Vite build assets: content-hashed → cache-first (safe forever) ──
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

  // ── HTML navigation: network-first → cached page → offline fallback ──
  if (req.mode === 'navigate') {
    event.respondWith(networkFirstNav(req));
    return;
  }

  // ── Everything else: try network, fallback to cache ──────────────────
  event.respondWith(
    fetch(req).catch(() => caches.match(req))
  );
});

// ── Cache strategies ────────────────────────────────────────────────────────

async function cacheFirst(req, cacheName) {
  const cached = await caches.match(req);
  if (cached) return cached;
  try {
    const response = await fetch(req);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(req, response.clone());
    }
    return response;
  } catch {
    return new Response('', { status: 408, statusText: 'Offline' });
  }
}

async function networkFirstNav(req) {
  const cache = await caches.open(PAGE_CACHE);
  try {
    const response = await fetch(req);
    if (response.ok) cache.put(req, response.clone());
    return response;
  } catch {
    const cached = await cache.match(req);
    if (cached) return cached;
    const offline = await caches.match('/offline.html');
    return offline || new Response(
      '<html><body style="font-family:system-ui;background:#0b1120;color:#e5e7eb;display:flex;align-items:center;justify-content:center;height:100vh;text-align:center"><div><h1>Sem conexão</h1><p>Tente novamente quando estiver online.</p></div></body></html>',
      { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
  }
}
