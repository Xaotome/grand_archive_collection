// Service Worker basique pour Grand Archive Collection
const CACHE_NAME = 'grand-archive-collection-v1';
const urlsToCache = [
    '/',
    '/index.php',
    '/assets/css/style.css',
    '/assets/js/api.js',
    '/assets/js/collection.js',
    '/assets/js/main.js'
];

// Installation du service worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache ouvert');
                return cache.addAll(urlsToCache.map(url => {
                    return new Request(url, { cache: 'reload' });
                }));
            })
            .catch(error => {
                console.log('Erreur lors de la mise en cache:', error);
            })
    );
});

// Activation du service worker
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Suppression ancien cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Intercepter les requêtes
self.addEventListener('fetch', event => {
    // Ne pas mettre en cache les requêtes API
    if (event.request.url.includes('/api/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Retourner le cache si disponible
                if (response) {
                    return response;
                }

                // Sinon, faire la requête réseau
                return fetch(event.request).catch(error => {
                    console.log('Erreur de requête réseau:', error);
                    // Retourner une page d'erreur basique en cas de problème
                    return new Response('Application hors ligne', {
                        status: 200,
                        headers: { 'Content-Type': 'text/plain' }
                    });
                });
            })
    );
});