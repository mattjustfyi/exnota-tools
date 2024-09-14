<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    workspace: {
        type: Object,
        required: true
    },
    refreshInProgress: {
        type: Boolean,
        required: true
    }
});

const fetchUserData = () => {
    axios.post(`/test/workspace/${props.workspace.id}/refresh`)
        .then(response => {
            console.log(response);
            // response.data
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
        });
};
</script>

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
                    <h3 class="text-lg font-medium text-gray-900">Workspace ID: {{ workspace.id }}</h3>
                    <p class="mt-4 text-gray-600">This is the workspace page. You can add more content and functionality
                        here.
                    </p>
                    <button @click="fetchUserData"
                        class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Fetch User Data
                    </button>

                    <div class="mt-8">
                        <h4 class="text-lg font-medium text-gray-900">Notion Objects:</h4>
                        <ul v-if="workspace.notion_objects && workspace.notion_objects.length" class="mt-4 space-y-2">
                            <li v-for="object in workspace.notion_objects" :key="object.id"
                                class="p-4 bg-gray-100 rounded">
                                <strong>{{ object.name }}</strong> ({{ object.type }})
                                <pre class="mt-2 text-sm">{{ JSON.stringify(object.data, null, 2) }}</pre>
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-gray-600">No Notion Objects available for this workspace.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
