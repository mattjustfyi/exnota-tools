<script setup>
import Heading from '@/Components/Heading.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, onBeforeMount, ref } from 'vue';

const isWindowOpened = ref(false);

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    workspaces: {
        type: Array,
        default: () => [],
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

const reload = () => {
    window.location.reload();
};

const url = "https://api.notion.com/v1/oauth/authorize?client_id=" +
    props.notionClientId +
    "&response_type=code&owner=user&redirect_uri=" +
    encodeURIComponent(props.notionRedirectUri);

const openNewWindow = () => {
    window.open(url, '_blank');
    isWindowOpened.value = true;
};

const shouldNavigateToWorkspace = computed(() => {
    return props.workspaces && props.workspaces.length > 0;
});

const navigateToWorkspace = () => {
    if (shouldNavigateToWorkspace.value) {
        const firstWorkspaceId = props.workspaces[0].id;
        router.visit(`/duplicates/workspace/${firstWorkspaceId}`);
    }
};

onBeforeMount(() => {
    navigateToWorkspace();
});
</script>

<template>
    <AppLayout>

        <Head title="Duplicates - Exnota Tools - Find/delete duplicate Notion database rows" />
        <div class="bg-background">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-background overflow-hidden p-6">

                    <template v-if="shouldNavigateToWorkspace">
                        <div class="flex justify-center items-center h-1/2 mt-32">
                            <span class="loading loading-spinner loading-lg"></span>
                        </div>
                    </template>

                    <template v-else>
                        <template v-if="isWindowOpened">
                            <div class="flex flex-col justify-center items-center h-1/2 mt-32">
                                <span class="loading loading-spinner loading-lg"></span>
                                <p class="text-center mt-8">Opening Notion so you can connect.</p>
                                <p class="text-center mt-4">If this message lasts more than a few seconds, please try
                                    again.
                                </p>
                                <button @click="reload" class="btn btn-primary mt-8">
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

                            <img src="/images/duplicates/notion-auth-short.gif"
                                alt="On the next screen, login to Notion, select your database, and select Allow Access"
                                class="w-full mb-6 border border-gray-300" />

                            <div class="flex justify-end">
                                <button @click="openNewWindow" class="btn btn-primary">
                                    Go to Notion
                                </button>
                            </div>
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
