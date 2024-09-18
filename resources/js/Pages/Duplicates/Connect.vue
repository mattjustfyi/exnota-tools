<script setup>
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
    window.open(url, '_blank', 'width=500,height=600');
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
    <AppLayoutNoUser>

        <Head title="Duplicates - Exnota Tools - Find/delete duplicate Notion database rows" />
        <h1 class="text-3xl font-bold mb-4">Find/Delete duplicate Rows</h1>
        <h2 class="text-2xl font-semibold mb-4">Connect</h2>
        <div v-if="user">
            <p>Welcome, {{ user.name }}</p>
            <h3 class="text-xl font-semibold mb-2">Your Workspaces:</h3>
            <ul v-if="workspaces && workspaces.length > 0">
                <li v-for="workspace in workspaces" :key="workspace.id">
                    {{ JSON.stringify(workspace) }}
                </li>
            </ul>
            <p v-else-if="workspaces && workspaces.length === 0">You don't have any workspaces yet.</p>
            <p v-else>Loading workspaces...</p>
            <br>
            <button @click="openNewWindow" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Connect to Notion</button>
        </div>
        <div v-else>
            <button @click="openNewWindow" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Connect to Notion</button>
        </div>
    </AppLayoutNoUser>
</template>
