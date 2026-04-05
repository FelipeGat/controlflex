// Force Service Worker update - open this in console to cleanup old SW
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.getRegistrations().then((registrations) => {
        console.log(
            `[SW-Cleanup] Found ${registrations.length} Service Worker registration(s)`,
        );
        for (let registration of registrations) {
            registration
                .unregister()
                .then((success) => {
                    console.log(
                        `[SW-Cleanup] Unregistered: ${registration.scope}`,
                    );
                })
                .catch((err) => {
                    console.error(`[SW-Cleanup] Failed to unregister:`, err);
                });
        }

        // Clear all caches
        if ("caches" in window) {
            caches.keys().then((cacheNames) => {
                console.log(`[SW-Cleanup] Found ${cacheNames.length} cache(s)`);
                cacheNames.forEach((cacheName) => {
                    caches.delete(cacheName).then((success) => {
                        console.log(`[SW-Cleanup] Deleted cache: ${cacheName}`);
                    });
                });
            });
        }

        setTimeout(() => {
            console.log("[SW-Cleanup] Done. Reloading page...");
            location.reload();
        }, 1000);
    });
}
