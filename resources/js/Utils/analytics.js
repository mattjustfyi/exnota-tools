export function event(name) {
    // Wrap in try/catch because this shouldn't break the app
    try {
        if (import.meta.env.MODE !== "development") {
            fathom.trackEvent(name);
            umami.track(name);
        } else {
            console.log("Event:", name);
        }
    } catch (e) {
        console.error("Error tracking event '" + name + "'", e);
    }
}
