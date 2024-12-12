<script setup>
import Heading from '@/Components/Heading.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, onBeforeMount, ref } from 'vue';
import { event } from '@/Utils/analytics.js';
import posthog from "posthog-js";

const isWaiting = ref(window.location.search.includes('status=waiting'));
const isError = ref(window.location.search.includes('status=error'));

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    notionClientId: {
        type: String,
        required: true
    },
    notionRedirectUri: {
        type: String,
        required: true
    }
});

const hardReload = () => {
    event('duplicates.connect.hardReload');
    // Remove 'waiting' request param
    const url = new URL(window.location);
    url.searchParams.delete('status');
    window.history.replaceState({}, document.title, url);

    window.location.reload();
};

const authUrl = "https://api.notion.com/v1/oauth/authorize?client_id=" +
    props.notionClientId +
    "&response_type=code&owner=user&redirect_uri=" +
    encodeURIComponent(props.notionRedirectUri);

const authWindow = ref(null);

let reloadTimeout = null;
const scheduleReload = () => {
    reloadTimeout = setTimeout(() => {
        router.reload({
            onFinish: () => {
                scheduleReload();
            }
        });
    }, 5000);
};

const openNewWindow = () => {
    event('duplicates.connect.goToNotion');

    authWindow.value = window.open(authUrl, '_blank');
    isWaiting.value = true;

    // Set waiting=true in URL
    const url = new URL(window.location);
    url.searchParams.set('status', 'waiting');
    window.history.replaceState({}, document.title, url);

    scheduleReload();
};

onBeforeMount(() => {
    const url = new URL(window.location);
    url.searchParams.delete('status');
    window.history.replaceState({}, document.title, url);

    window.notionAuthSuccess = () => {
        event('duplicates.connect.notionAuthSuccess');
        clearTimeout(reloadTimeout);

        authWindow.value.close();

        // Remove 'waiting' request param
        const url = new URL(window.location);
        url.searchParams.delete('status');
        window.history.replaceState({}, document.title, url);

        // Backend will redirect
        router.reload();
    };
    window.notionAuthError = () => {
        event('duplicates.connect.notionAuthError');
        clearTimeout(reloadTimeout);

        authWindow.value.close();

        isError.value = true;

        // Remove 'waiting' request param
        const url = new URL(window.location);
        url.searchParams.delete('status');
        window.history.replaceState({}, document.title, url);
    };
});

</script>

<template>
    <AppLayout :feature="{
        title: 'Delete Duplicates',
        href: route('duplicates'),
    }">

        <Head title="Delete duplicates in Notion databases - Exnota Tools" />

        <template v-if="isError">
            <div class="flex flex-col justify-center items-center h-1/2 mt-32">
                <p class="text-center">There was an error connecting to Notion.</p>
                <button @click="hardReload" class="btn btn-primary mt-8">
                    Try again
                </button>
            </div>
        </template>
        <template v-else-if="isWaiting">
            <div class="flex flex-col justify-center items-center h-1/2 mt-32">
                <span class="loading loading-spinner loading-lg"></span>
                <p class="text-center mt-8">Opening Notion so you can connect.</p>
                <p class="text-center mt-4">If this message lasts more than a few seconds, please try
                    again.
                </p>
                <button @click="hardReload" class="btn btn-primary mt-8">
                    Try again
                </button>
            </div>
        </template>
        <template v-else>
            <div class="mb-6">
                <Heading :level="1" class="truncate">
                    1/4 Connect to Notion
                </Heading>
                <p>Select your database in Notion and press <strong>Allow Access</strong>.</p>
            </div>

            <img src="/images/duplicates/notion-auth-short-slow.gif"
                alt="On the next screen, login to Notion, select your database, and select Allow Access"
                class="w-full max-w-2xl mx-auto mb-6 border border-gray-300" />

            <div class="flex justify-end">
                <button @click="openNewWindow" class="btn btn-primary">
                    Go to Notion
                </button>
            </div>
        </template>
    </AppLayout>
</template>
