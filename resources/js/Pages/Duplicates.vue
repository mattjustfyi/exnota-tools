<script setup>
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head } from "@inertiajs/vue3";

defineProps({
    user: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <AppLayoutNoUser>
        <Head title="Welcome" />
        <h1>Welcome</h1>
        <p>Hello {{ user?.name ?? 'Guest' }}, welcome to your first Inertia app!</p>
    </AppLayoutNoUser>
</template>
