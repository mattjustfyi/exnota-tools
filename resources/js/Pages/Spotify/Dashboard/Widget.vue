<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, ref } from 'vue';

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

const firstArtistName = computed(() => {
    return (currentTrack.value?.artists.length ?? 0) > 0 ? currentTrack.value.artists[0].name : 'Unknown Artist';
});

</script>

<template>

    <Head title="Melodash" />
    <div class="spotify-widget">
        <div v-if="currentTrack" class="widget-content">
            <img class="album-art" :src="currentTrack.album_image_url" :alt="albumName">
            <div class="track-info">
                <h2 class="track-title">{{ currentTrack.name }}</h2>
                <p class="artist-name">{{ firstArtistName }}</p>
            </div>
            <div class="controls">
                <button class="more-options">•••</button>
                <button class="play-button">
                    <span class="play-icon">▶</span>
                </button>
            </div>
        </div>
        <div v-else class="widget-content">
            <p class="not-playing">Not Playing</p>
        </div>
    </div>
</template>


<style scoped>
.spotify-widget {
    background: linear-gradient(to bottom, #2c3e50, #1a252f);
    border-radius: 8px;
    padding: 8px;
    color: white;
    width: 100%;
    max-width: 600px;
}

.widget-content {
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
.play-button:hover {
    transform: scale(1.1);
}

.not-playing {
    font-size: 16px;
    color: #a0a0a0;
    text-align: center;
    width: 100%;
}
</style>