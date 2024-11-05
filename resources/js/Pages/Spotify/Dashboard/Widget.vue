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
                    <span class="track-duration">{{ trackDuration }}</span>
                    <!-- SVG Animation -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
                        <style>
                            .wave {
                                animation: wave 1s ease-in-out infinite alternate;
                                transform-origin: center;
                            }

                            @keyframes wave {
                                0% {
                                    transform: scaleY(0.8);
                                }

                                100% {
                                    transform: scaleY(1);
                                }
                            }
                        </style>

                        <rect class="wave" x="7.5" y="25" width="5" height="14" fill="rgba(255, 255, 255, 0.7)">
                            <animate attributeName="height" values="14;20;14" dur="0.8s" repeatCount="indefinite" />
                            <animate attributeName="y" values="18;15;18" dur="0.8s" repeatCount="indefinite" />
                        </rect>

                        <rect class="wave" x="17.5" y="25" width="5" height="20" fill="rgba(255, 255, 255, 0.7)">
                            <animate attributeName="height" values="20;26;20" dur="1s" repeatCount="indefinite" />
                            <animate attributeName="y" values="15;12;15" dur="1s" repeatCount="indefinite" />
                        </rect>

                        <rect class="wave" x="27.5" y="25" width="5" height="8" fill="rgba(255, 255, 255, 0.7)">
                            <animate attributeName="height" values="8;16;8" dur="1.2s" repeatCount="indefinite" />
                            <animate attributeName="y" values="21;17;21" dur="1.2s" repeatCount="indefinite" />
                        </rect>

                        <rect class="wave" x="37.5" y="25" width="5" height="16" fill="rgba(255, 255, 255, 0.7)">
                            <animate attributeName="height" values="16;22;16" dur="0.9s" repeatCount="indefinite" />
                            <animate attributeName="y" values="17;14;17" dur="0.9s" repeatCount="indefinite" />
                        </rect>
                    </svg>

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