<script setup>
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    user: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AppLayoutNoUser>

        <Head title="Duplicates - Exnota Tools - Find/delete duplicate Notion database rows" />
        <h1>Find/Delete duplicate Rows</h1>
        <p>User: {{ user }}</p>
        <Link href="/duplicates/connect">Start</Link>
    </AppLayoutNoUser>
</template>
