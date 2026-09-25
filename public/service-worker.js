// A propósito NO cachea páginas HTML ni nada dinámico (carritos, stock,
// precios, sesión) — solo existe para que el navegador considere el sitio
// "instalable" y para acelerar la carga de archivos verdaderamente
// estáticos. Cachear una página HTML por error podría mostrarle a un
// cajero una pantalla vieja con datos desactualizados.
const CACHE_NAME = 'estáticos-v1';
const RUTAS_CACHEABLES = ['/icons/', '/build/', '/css/', '/js/', '/storage/logos/'];

self.addEventListener('install', (evento) => {
    self.skipWaiting();
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches.keys().then((nombres) => Promise.all(
            nombres.filter((nombre) => nombre !== CACHE_NAME).map((nombre) => caches.delete(nombre))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (evento) => {
    const request = evento.request;

    // Nunca interceptar nada que no sea GET (forms de login/cobro/etc.).
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Nunca interceptar navegación entre páginas ni pedidos de otro origen
    // (ej. el CDN de Tailwind) — siempre van directo a la red.
    if (request.mode === 'navigate' || url.origin !== self.location.origin) {
        return;
    }

    const esCacheable = RUTAS_CACHEABLES.some((prefijo) => url.pathname.startsWith(prefijo));

    if (!esCacheable) {
        return;
    }

    evento.respondWith(
        caches.open(CACHE_NAME).then((cache) => cache.match(request).then((cacheado) => {
            if (cacheado) {
                return cacheado;
            }

            return fetch(request).then((respuesta) => {
                cache.put(request, respuesta.clone());

                return respuesta;
            });
        }))
    );
});
