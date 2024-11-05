import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Pusher is needed for Reverb:
// "we will also install the pusher-js package since Reverb utilizes the Pusher
// protocol for WebSocket subscriptions, channels, and messages"
window.Pusher = Pusher;

console.log("VITE_REVERB_APP_KEY:", import.meta.env.VITE_REVERB_APP_KEY);
console.log("VITE_REVERB_HOST:", import.meta.env.VITE_REVERB_HOST);
console.log("VITE_REVERB_PORT:", import.meta.env.VITE_REVERB_PORT);
console.log("VITE_REVERB_SCHEME:", import.meta.env.VITE_REVERB_SCHEME);

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});
