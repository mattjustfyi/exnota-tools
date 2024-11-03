<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
    const channel = window.Echo.channel('spotify-dashboard-current-playing')
    const listener = channel.listen('.CurrentPlaying', (event) => {
        console.log('CurrentPlaying event received:', event);
        // Handle the event data here
    });
    console.log('Listening for CurrentPlaying events');
});

onUnmounted(() => {
    window.Echo.leave('spotify-dashboard-current-playing');
});
</script>

<template>

    <Head title="Exnota Tools - Delete duplicates in Notion, and more." />
    <div class="container xl:max-w-5xl mx-auto px-4">
        <h1 class="text-4xl font-bold text-center mt-24">Exnota Tools</h1>
        <p class=" text-center mt-4">Free tools for Notion.</p>

        <div class="card card-side w-full bg-base-100 mt-24 shadow-xl">
            <figure class="max-w-xs w-64">
                <img src="/images/duplicates/search-results-sample2.png" />
            </figure>
            <div class="card-body min-w-sm">
                <h2 class="card-title">Delete Duplicates in Notion</h2>
                <p>Delete database entries with identical titles.</p>
                <div class="card-actions justify-end">
                    <a class="btn btn-primary" :href="route('duplicates')">Delete Duplicates</a>
                </div>
            </div>
        </div>

        <div class="divider my-16"></div>

        <div class="card bg-base-100 w-full shadow-xl mx-auto">
            <div class="card-body">
                <h2 class="card-title text-center mx-auto">More coming soon</h2>
            </div>
        </div>
    </div>
</template>
