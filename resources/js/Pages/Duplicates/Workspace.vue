<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    workspaceId: {
        type: Number,
        required: true
    }
});

const fetchUserData = () => {
    fetch(`/test/workspace/${props.workspaceId}/refresh`)
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

<!-- TODO:
 
- Backend returns any active workspace loading jobs.
- If one is in progress, show a spinner
- If there isn't one, or it errored, show an error
- If there is one and it is done, show the workspace notion_objects

-->

<template>
    <AppLayout>

        <Head title="Workspace" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Workspace
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">Workspace ID: {{ workspaceId }}</h3>
                    <p class="mt-4 text-gray-600">This is the workspace page. You can add more content and functionality
                        here.
                    </p>
                    <button @click="fetchUserData"
                        class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Fetch User Data
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
