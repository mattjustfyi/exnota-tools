<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
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
    <AppLayout>

        <Head title="Duplicates - Exnota Tools - Find/delete duplicate Notion database rows" />
        <div class="hero">
            <div class="hero-content p-0 flex-col lg:flex-row-reverse">
                <img src="/images/duplicates/search-results-sample2.png" class="max-w-full w-64 shadow-lg rounded-xl" />
                <div>
                    <h1 class="text-4xl font-bold leading-normal">Delete duplicates in a Notion
                        database.<span class="block">Without
                            tricks.</span></h1>
                    <p class="py-6">
                        In just a few clicks, delete database entries with <strong>the same title</strong>, so you
                        can avoid the hassle of using database tricks or cleaning up CSVs and starting from scratch.
                    </p>
                    <div class="flex justify-end lg:justify-start">
                        <Link href="/duplicates/connect" class="btn btn-primary px-8">
                        Start
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 mt-16">
            <h3 class="text-xl font-bold">How long does it take?</h3>
        </div>

        <div class="flex justify-between">
            <div class="stats stats-vertical sm:stats-horizontal shadow">
                <div class="stat">
                    <div class="stat-title text-primary">100 duplicates</div>
                    <div class="stat-value">~20 sec</div>
                </div>
                <div class="stat">
                    <div class="stat-title text-primary">1,000 duplicates</div>
                    <div class="stat-value">~20 min</div>
                </div>
            </div>
        </div>


    </AppLayout>
</template>
