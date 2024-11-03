<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    spotifyDashboard: {
        id: Number,
        workspace_id: Number,
        artist_db_notion_id: String,
        album_db_notion_id: String,
        track_db_notion_id: String,
        play_history_db_notion_id: String,
        dashboard_page_notion_id: String,
        created_at: [String, null],
        updated_at: String,
        play_status: String,
        current_track: {
            name: String,
            spotify_id: String,
            url: String,
            duration_ms: Number,
            artists: Array,
            album_name: String,
            album_spotify_id: String,
            album_url: String,
            album_image_url: String
        },
        spotify_track_id_in_notion: String
    }
});

const currentTrack = ref(props.spotifyDashboard.current_track);

const artistNames = computed(() => {
    return currentTrack.value?.artists.map(artist => artist.name).join(', ') || 'Unknown Artist';
});

const trackDuration = computed(() => {
    if (!currentTrack.value) return '00:00';
    const minutes = Math.floor(currentTrack.value.duration_ms / 60000);
    const seconds = Math.floor((currentTrack.value.duration_ms % 60000) / 1000).toString().padStart(2, '0');
    return `${minutes}:${seconds}`;
});

// Theme management
// Save to local storage instead of cookies as they are considered third-party
// and will likely be blocked by browsers. Another option may be to save this
// server-side, though that would prohibit different theme mode per device.
const theme = ref(localStorage.getItem('theme') || 'light');

const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light';
    localStorage.setItem('theme', theme.value);
    document.body.className = theme.value; // Apply the theme class to the body
};

onMounted(() => {
    document.body.className = theme.value; // Apply the theme class to the body on mount
});

onMounted(() => {
    const channel = window.Echo.channel('spotify.dashboard.3')
    const listener = channel.listen('.CurrentPlaying', (event) => {
        console.log('CurrentPlaying event received:', event);
        // Handle the event data here
    });
    console.log('Listening for CurrentPlaying events');
});

onUnmounted(() => {
    window.Echo.leave('spotify.dashboard.3');
});

</script>

<template>
    <div :class="theme">

        <Head title="Melodash" />
        <div class="spotify-widget">
            <div v-if="currentTrack" class="widget-content">
                <div class="track-details">
                    <img class="album-art" :src="currentTrack.album_image_url" :alt="albumName">
                    <div class="track-info">
                        <h2 class="track-title">{{ currentTrack.name }}</h2>
                        <p class="artist-name">{{ artistNames }}</p>
                    </div>
                </div>
                <div class="controls">
                    <button class="theme-toggle" @click="toggleTheme">
                        <svg v-if="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                    </button>
                    <span class="track-duration">{{ trackDuration }}</span>
                </div>
            </div>
            <div v-else class="widget-content">
                <p class="not-playing">Not Playing</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.spotify-widget {
    background: linear-gradient(to bottom, #2c3e50, #1a252f);
    border-radius: 8px;
    color: white;
    width: 100vw;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.widget-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0 16px;
}

.track-details {
    display: flex;
    align-items: center;
    gap: 16px;
}

.album-art {
    width: 56px;
    height: 56px;
    border-radius: 4px;
    object-fit: cover;
}

.track-info {
    flex-grow: 1;
}

.track-title {
    font-size: 16px;
    font-weight: bold;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.artist-name {
    font-size: 14px;
    color: #a0a0a0;
    margin: 4px 0 0 0;
}

.controls {
    display: flex;
    align-items: center;
    gap: 16px;
}

.track-duration {
    font-size: 14px;
    color: #a0a0a0;
}

.theme-toggle {
    background: none;
    border: none;
    color: #a0a0a0;
    font-size: 16px;
    cursor: pointer;
    padding: 8px;
    opacity: 0;
    transition: opacity 0.3s;
}

.spotify-widget:hover .theme-toggle {
    opacity: 1;
}

.more-options {
    background: none;
    border: none;
    color: #a0a0a0;
    font-size: 16px;
    cursor: pointer;
    padding: 8px;
}

.play-button {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.play-icon {
    color: black;
    font-size: 12px;
    margin-left: 2px;
}

.more-options:hover,
.play-button:hover,
.theme-toggle:hover {
    transform: scale(1.1);
}

.not-playing {
    font-size: 16px;
    color: #a0a0a0;
    text-align: center;
    width: 100%;
}
</style>

<style>
body.dark {
    background-color: #191919;
}

body.light {
    background-color: #ffffff;
}

/* body {
    background-color: #191919;
} */
</style>
