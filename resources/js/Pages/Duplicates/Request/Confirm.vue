<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    toolDuplicateRequest: {
        type: Object,
        required: true
    },
});
</script>

<template>
    <AppLayout>
        <Head title="Confirm Duplicate Request" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Confirm Duplicate Request
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">Search Results</h3>
                    <p class="mt-4 text-gray-600">
                        Notion Object ID: {{ toolDuplicateRequest.notion_object_id }}
                    </p>
                    <p class="mt-2 text-gray-600">
                        Searched At: {{ new Date(toolDuplicateRequest.searched_at).toLocaleString() }}
                    </p>

                    <div class="mt-8">
                        <h4 class="text-lg font-medium text-gray-900">Search Results:</h4>
                        <ul v-if="toolDuplicateRequest.search_results && toolDuplicateRequest.search_results.length" class="mt-4 space-y-4">
                            <li v-for="result in toolDuplicateRequest.search_results" :key="result.id" class="p-4 bg-gray-100 rounded">
                                <div><strong>ID:</strong> {{ result.id }}</div>
                                <div><strong>Name:</strong> {{ result.name }}</div>
                                <div v-if="result.properties">
                                    <strong>Properties:</strong>
                                    <ul class="ml-4 list-disc">
                                        <li v-for="(value, key) in result.properties" :key="key">
                                            {{ key }}: {{ JSON.stringify(value) }}
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-gray-600">No search results available.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
