<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from "@inertiajs/vue3";
import { ref, watch, computed } from 'vue';

// TODO: Save to local storage so refresh doesn't lose

const iconType = ref('none');
const iconEmojiValue = ref('');
const iconIconValue = ref('');

watch(iconType, async (newIconType, oldiconType) => {
    console.log('Icon type changed from', oldiconType, 'to', newIconType);
})

const coverUrl = ref('');

const json = computed(() => {
    const reqBody = {};

    if (iconType.value === 'emoji') {
        reqBody.icon = {
            type: 'emoji',
            emoji: iconEmojiValue.value,
        };
    } else if (iconType.value === 'icon') {
        reqBody.icon = {
            type: 'external',
            url: iconIconValue.value,
        };
    }

    if (coverUrl.value) {
        reqBody.cover = {
            type: 'external',
            url: coverUrl.value,
        };
    }

    return reqBody;
});

const copyToClipboard = () => {
    const textToCopy = JSON.stringify(json.value, null, 2);
    navigator.clipboard.writeText(textToCopy).then(() => {
        console.log('Text copied to clipboard');
    }).catch(err => {
        console.error('Failed to copy text: ', err);
    });
};

</script>

<template>
    <AppLayout :feature="{
        title: 'Music Dashboard',
        href: route('music-dashboard'),
    }">

        <Head title="Generate Notion API requests - Exnota Tools" />

        <div class="hero bg-base-200">
            <div class="hero-content flex-col lg:flex-row-reverse">
                <iframe src="https://www.youtube-nocookie.com/embed/RFF28KVLz7Q?si=pi5G6nh6gLYaYHv7"
                    class="aspect-video w-full" title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                <!-- <img src="https://img.daisyui.com/images/stock/photo-1635805737707-575885ab0820.webp"
                    class="max-w-sm rounded-lg shadow-2xl" /> -->
                <div>
                    <h1 class="text-5xl font-bold">Sync Spotify to Notion</h1>
                    <div class="prose mr-16">
                        <ul class="py-6">
                            <li>No sync limits</li>
                            <li>Currently Playing widget</li>
                            <li>Live updating page title and icon</li>
                            <li>Full databases with relations for Songs, Artists, Albums, and Play History.</li>
                            <li>Images and links for all artists.</li>
                        </ul>
                    </div>
                    <a href="https://mattjustfyi.gumroad.com/l/kleqhd" class="btn btn-primary" target="_blank">Buy
                        the Premium Music Dashboard</a>
                </div>
            </div>
        </div>

        <div class="divider my-16"></div>

        <h2 class="text-3xl font-bold">Music Dashboard Lite</h2>
        <p class="prose">Create your own Spotify to Notion sync with a free Make account and some setup.</p>
        <h3 class="text-2xl font-bold">Notion API Helper</h3>
        <label class="form-control w-full max-w-xs">
            <div class="label">
                <span class="label-text">Icon</span>
            </div>
            <select class="select select-bordered" v-model="iconType">
                <option value="none" checked="">None</option>
                <option value="emoji">Emoji</option>
                <option value="icon">Icon</option>
            </select>
        </label>

        <label v-if="iconType === 'emoji'" class="form-control w-full max-w-xs">
            <div class="label">
                <span class="label-text">Emoji</span>
            </div>
            <input type="text" placeholder="Enter emoji" class="input input-bordered w-full max-w-xs"
                v-model="iconEmojiValue" />
        </label>

        <label v-if="iconType === 'icon'" class="form-control w-full max-w-xs">
            <div class="label">
                <span class="label-text">Icon URL</span>
            </div>
            <input type="url" placeholder="Enter icon URL" class="input input-bordered w-full max-w-xs"
                v-model="iconIconValue" />
        </label>

        <label class="form-control w-full max-w-xs">
            <div class="label">
                <span class="label-text">Cover</span>
            </div>
            <input type="url" placeholder="Enter URL" class="input input-bordered w-full max-w-xs" v-model="coverUrl" />
            <div class="label">
                <span class="label-text-alt">Leave blank for none</span>
            </div>
        </label>

        <textarea class="textarea w-full h-64" disabled>{{ JSON.stringify(json, null, 2) }}</textarea>
        <br>
        <button class="btn btn-primary" @click="copyToClipboard">
            <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none"
                xmlns="http://www.w3.org/2000/svg" color="#fff">
                <path
                    d="M19.4 20H9.6C9.26863 20 9 19.7314 9 19.4V9.6C9 9.26863 9.26863 9 9.6 9H19.4C19.7314 9 20 9.26863 20 9.6V19.4C20 19.7314 19.7314 20 19.4 20Z"
                    stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                <path
                    d="M15 9V4.6C15 4.26863 14.7314 4 14.4 4H4.6C4.26863 4 4 4.26863 4 4.6V14.4C4 14.7314 4.26863 15 4.6 15H9"
                    stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            Copy
        </button>
    </AppLayout>
</template>
