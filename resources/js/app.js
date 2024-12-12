import "./bootstrap";
import "../css/app.css";

import { createApp, h, nextTick } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import "posthog-js/dist/recorder";
import "posthog-js/dist/exception-autocapture";
import "posthog-js/dist/tracing-headers";
import "posthog-js/dist/web-vitals";
import posthog from "posthog-js";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});

// Refresh the page on back navigate to get updated data
// From: https://github.com/inertiajs/inertia/issues/565#issuecomment-895225604
let stale = false;

window.addEventListener("popstate", () => {
    stale = true;
});

router.on("navigate", (event) => {
    // https://posthog.com/tutorials/single-page-app-pageviews
    nextTick(() => {
        console.log("posthog pageview", event.detail.page.url);
        const path = window.location.origin + event.detail.page.url;
        posthog.capture("$pageview", {
            path: path,
        });
    });
    if (stale) {
        stale = false;
        router.reload();
    }
});

/* Posthog analytics */
posthog.init("phc_Hw7oDphoNG3KRT1M6KmlLAAfV9dQfSmJFcqFor3QFeE", {
    api_host: "https://eu.i.posthog.com",
    persistence: "memory",
    capture_pageview: false,
    loaded: function (ph) {
        if (import.meta.env.MODE === "development") {
            console.log("Opting out of Posthog event capture");
            ph.opt_out_capturing(); // opts a user out of event capture
            ph.set_config({ disable_session_recording: true });
        }
    },
});
