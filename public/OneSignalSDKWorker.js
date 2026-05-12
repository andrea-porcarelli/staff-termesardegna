importScripts("https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js");

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch (_) {
        return;
    }

    const count = payload?.custom?.a?.unread_count
        ?? payload?.a?.unread_count
        ?? payload?.unread_count;

    if (typeof count !== 'number' || !self.navigator || !('setAppBadge' in self.navigator)) {
        return;
    }

    event.waitUntil(
        (count > 0
            ? self.navigator.setAppBadge(count)
            : self.navigator.clearAppBadge()
        ).catch(() => {})
    );
});
