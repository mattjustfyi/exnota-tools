<script setup>
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    user: {
        type: Object,
        default: null,
    },
});

const fetchUserData = () => {
    fetch('/test/workspace/36/refresh', {
        method: 'POST'
    })
        .then(response => {
            console.log(response);
            // response.json()
        })
        .then(data => {
            console.log(data);
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
        });
};
</script>

<template>
    <AppLayoutNoUser>

        <Head title="Duplicates - Exnota Tools - Find/delete duplicate Notion database rows" />
        <h1>Find/Delete duplicate Rows</h1>
        <p>User: {{ user }}</p>
        <Link href="/duplicates/connect">Start</Link>
        <br><br>
        <button @click="fetchUserData">Refresh Workspace</button>
    </AppLayoutNoUser>
</template>
