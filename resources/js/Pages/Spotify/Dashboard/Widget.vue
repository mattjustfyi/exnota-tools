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
            album_image_url: String,
            album_colors: {
                type: Array,
                required: false,
                default: () => []
            }
        },
    },
    spotify_track_id_in_notion: String
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

const albumColors = computed(() => {
    return currentTrack.value?.album_colors || [];
});

onMounted(() => {
    const channel = window.Echo.channel('spotify.dashboard.' + props.spotifyDashboard.id)
    const listener = channel.listen('.CurrentPlayingUpdated', (event) => {
        console.log('CurrentPlayingUpdated event received:', event);
        currentTrack.value = event.current_track;
    });
    console.log('Listening for CurrentPlayingUpdated events');
});

onUnmounted(() => {
    window.Echo.leave('spotify.dashboard.3');
});

const widgetBackground = computed(() => {
    if (albumColors.value.length > 0) {
        const firstColor = albumColors.value[0];
        const darkenColor = (color, amount) => {
            let usePound = false;
            if (color[0] === "#") {
                color = color.slice(1);
                usePound = true;
            }
            const num = parseInt(color, 16);
            let r = (num >> 16) + amount;
            let g = ((num >> 8) & 0x00FF) + amount;
            let b = (num & 0x0000FF) + amount;
            r = r > 255 ? 255 : r < 0 ? 0 : r;
            g = g > 255 ? 255 : g < 0 ? 0 : g;
            b = b > 255 ? 255 : b < 0 ? 0 : b;
            return (usePound ? "#" : "") + (r << 16 | g << 8 | b).toString(16).padStart(6, '0');
        };
        const secondColor = darkenColor(firstColor, -30);
        return `linear-gradient(to bottom, ${firstColor}, ${secondColor})`;
    }
    return 'linear-gradient(to bottom, #2c3e50, #1a252f)';
});

</script>

<template>
    <div>

        <Head title="Melodash" />
        <div class="spotify-widget" :style="{ background: widgetBackground }">
            <div v-if="currentTrack" class="widget-content">
                <div class="track-details">
                    <img class="album-art" :src="currentTrack.album_image_url" :alt="albumName">
                    <div class="track-info">
                        <h2 class="track-title">{{ currentTrack.name }}</h2>
                        <p class="artist-name">{{ artistNames }}</p>
                    </div>
                </div>
                <div class="controls">
                    <!-- <button class="theme-toggle" @click="toggleTheme">
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
                    </button> -->
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
    color: rgba(255, 255, 255, 0.7);
    margin: 4px 0 0 0;
}

.album-colors {
    display: flex;
    gap: 4px;
    margin-top: 4px;
}

.color-square {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.controls {
    display: flex;
    align-items: center;
    gap: 16px;
}

.track-duration {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
}

/* .theme-toggle {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 16px;
    cursor: pointer;
    padding: 8px;
    opacity: 0;
    transition: opacity 0.3s;
} */

/* .spotify-widget:hover .theme-toggle {
    opacity: 1;
} */

.more-options {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
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
html {
    background: transparent !important;
}
</style>
