<script setup>
import Heading from '@/Components/Heading.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head, Link, router } from "@inertiajs/vue3";
import { onMounted } from 'vue';

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

const url = "https://api.notion.com/v1/oauth/authorize?client_id=" +
    props.notionClientId +
    "&response_type=code&owner=user&redirect_uri=" +
    encodeURIComponent(props.notionRedirectUri);

const openNewWindow = () => {
    window.open(url, '_blank');
};

const navigateToWorkspace = () => {
    if (props.workspaces && props.workspaces.length > 0) {
        const firstWorkspaceId = props.workspaces[0].id;
        router.visit(`/duplicates/workspace/${firstWorkspaceId}`);
    }
};

onMounted(() => {
    navigateToWorkspace();
});
</script>

<template>
    <AppLayout>

        <Head title="Duplicates - Exnota Tools - Find/delete duplicate Notion database rows" />
        <div class="bg-background">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-background overflow-hidden p-6">

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
                </div>
            </div>
        </div>
    </AppLayout>
</template>
