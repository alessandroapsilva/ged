/**
 * Service Worker - GED PWA
 * Versão 1.0
 */

const CACHE_NAME = 'ged-v1.0';
const BASE = '/ged';
const CACHE_URLS = [
    `${BASE}/painel_produtividade`,
    `${BASE}/documentos`,
    `${BASE}/assets/dist/css/adminlte.min.css`,
    `${BASE}/assets/dist/css/ged_styles.css`,
    `${BASE}/assets/dist/css/modern-theme.css`,
    `${BASE}/assets/dist/js/ged-modern.js`,
    `${BASE}/assets/plugins/fontawesome-free/css/all.min.css`
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Instalando Service Worker...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Cache aberto');
                return cache.addAll(CACHE_URLS);
            })
            .catch((err) => {
                console.error('[SW] Erro ao fazer cache:', err);
            })
    );
    self.skipWaiting();
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Ativando Service Worker...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Removendo cache antigo:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    return self.clients.claim();
});

// Intercepta requisições
self.addEventListener('fetch', (event) => {
    // Ignora requisições POST e API
    if (event.request.method !== 'GET' || event.request.url.includes('/api_')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Retorna do cache se encontrado
                if (response) {
                    return response;
                }

                // Caso contrário, busca na rede
                return fetch(event.request)
                    .then((response) => {
                        // Verifica se é uma resposta válida
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clona a resposta
                        const responseToCache = response.clone();

                        // Adiciona ao cache
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(() => {
                        // Retorna página offline se disponível
                        return caches.match(`${BASE}/offline.html`);
                    });
            })
    );
});

// Notificações Push (placeholder para implementação futura)
self.addEventListener('push', (event) => {
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: `${BASE}/assets/dist/img/icon-192.png`,
        badge: `${BASE}/assets/dist/img/badge-72.png`,
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: data.primaryKey
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Clique em notificação
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url || `${BASE}/`)
    );
});
