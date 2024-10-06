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

                    <div class="mb-6">
                        <Heading :level="1" class="truncate">
                            Connect to Notion
                        </Heading>
                        <p>Select your database in Notion and press <strong>Allow Access</strong>.</p>
                    </div>

                    <img src="/images/duplicates/notion-auth-short.gif"
                        alt="On the next screen, login to Notion, select your database, and select Allow Access"
                        class="w-full mb-6 border border-gray-300" />

                    <div class="flex justify-end">
                        <Link href="/duplicates/connect" class="btn btn-primary">
                        Go to Notion
                        </Link>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
