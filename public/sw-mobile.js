// Service worker untuk halaman /mobile/
// File di root (/) supaya scope /mobile/ valid tanpa header tambahan.

const CACHE_NAME = 'sikat-mobile-v1';

const REQUIRED_FILES = [
  '/assetsmobileui/css/style.css',
  '/assetsmobileui/css/inc/bootstrap/bootstrap.min.css',
  '/assetsmobileui/css/inc/owl-carousel/owl.carousel.min.css',
  '/assetsmobileui/css/inc/owl-carousel/owl.theme.default.css',
  '/assetsmobileui/js/lib/jquery-3.4.1.min.js',
  '/assetsmobileui/js/lib/popper.min.js',
  '/assetsmobileui/js/lib/bootstrap.min.js',
  '/assetsmobileui/js/plugins/owl-carousel/owl.carousel.min.js',
  '/assetsmobileui/js/plugins/jquery-circle-progress/circle-progress.min.js',
  '/assetsmobileui/js/base.js',
  '/assetsmobileui/img/favicon.png',
  '/assetsmobileui/img/icon/192x192.png',
  '/manifest-mobile.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(REQUIRED_FILES))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then((cached) => {
      if (cached) return cached;
      return fetch(event.request);
    })
  );
});

