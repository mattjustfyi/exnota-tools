<script setup>
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head, Link } from "@inertiajs/vue3";

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
        <p v-if="user">Welcome, {{ user.name }}</p>
        <p v-else>Not connected</p>
        <br>
        <button @click="openNewWindow">Connect to Notion</button>
    </AppLayoutNoUser>
</template>
