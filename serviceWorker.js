const URLS = [
    '/',
    '/privacy',
    '/profile',
    '/private',
    '/exceptions/not_found',
    '/exceptions/bad_request',
    '/exceptions/forbidden',
    '/exceptions/failed_dependency',
    '/exceptions/internal_server_error',
    '/exceptions/maintenance'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('response-store').then((cache) => {
            console.info('serviceWorker: installation succeeded.');

            return cache.addAll(URLS)
        })
    );
});

self.addEventListener('fetch', (event) => {
    if (
        Object.keys(event.request.headers).indexOf('x-online-test') > -1
        ||
        event.request.url.includes('onlineTest')
    ) {
        event.respondWith(
            fetch(event.request)
        );
    } else {
        event.respondWith(
            fetch(event.request).catch(() => {
                if (
                    URLS.indexOf(
                        event.request.url.substr(
                            event.request.url.indexOf('/', 7)
                        )
                    ) > -1
                ) {
                    return caches.match(event.request);
                } else {
                    return caches.match(new Request('/exceptions/failed_dependency'));
                }
            })
        );
    }
});