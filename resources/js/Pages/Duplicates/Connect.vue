<script setup>
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head, Link } from "@inertiajs/vue3";

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
</script>

<template>
    <AppLayoutNoUser>

        <Head title="Duplicates - Exnota Tools - Find/delete duplicate Notion database rows" />
        <h1>Find/Delete duplicate Rows</h1>
        <h2>Connect</h2>
        <br>
        <div v-if="user">
            <p>Welcome, {{ user.name }}</p>
            <h3>Your Workspaces:</h3>
            <ul v-if="workspaces && workspaces.length > 0">
                <li v-for="workspace in workspaces" :key="workspace.id">
                    {{ workspace.name }}
                </li>
            </ul>
            <p v-else-if="workspaces && workspaces.length === 0">You don't have any workspaces yet.</p>
            <p v-else>Loading workspaces...</p>
            <br>
            <button @click="openNewWindow">Connect to Notion</button>
        </div>
        <div v-else>
            <p>Not connected</p>
            <br>
            <button @click="openNewWindow">Connect to Notion</button>
        </div>
    </AppLayoutNoUser>
</template>
