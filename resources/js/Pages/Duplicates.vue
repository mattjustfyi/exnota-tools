<script setup>
import Heading from '@/Components/Heading.vue';
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
        <div class="bg-background">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-background overflow-hidden p-6">

                    <div class="hero">
                        <div class="hero-content flex-col lg:flex-row-reverse">
                            <img src="/images/duplicates/search-results-sample2.png"
                                class="max-w-full w-64 shadow-lg rounded-xl" />
                            <div>
                                <h1 class="text-4xl font-bold leading-normal">Delete duplicates in a Notion
                                    database.<span class="block">Without
                                        tricks.</span></h1>
                                <p class="py-6">
                                    Automatically delete database entries with <strong>the same title</strong>, so you
                                    don’t have to mess
                                    with database tricks or CSVs.
                                </p>
                                <div class="flex justify-end lg:justify-start">
                                    <Link href="/duplicates/connect" class="btn btn-primary px-8">
                                    Start
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </AppLayout>
</template>
