<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    toolDuplicateRequest: {
        type: Object,
        required: true
    },
    jobsInProgress: {
        type: Boolean,
        required: true
    },
});

const truncateTitle = (title, maxLength = 50) => {
    if (title.length <= maxLength) return title;
    return title.slice(0, maxLength) + '...';
};

const sortedSearchResults = computed(() => {
    if (props.toolDuplicateRequest.search_results && Array.isArray(props.toolDuplicateRequest.search_results)) {
        return [...props.toolDuplicateRequest.search_results].sort((a, b) => b.delete.length - a.delete.length);
    }
    return [];
});

console.log('toolDuplicateRequest', props.toolDuplicateRequest);
console.log('toolDuplicateRequest.search_results', props.toolDuplicateRequest.search_results);
if (props.toolDuplicateRequest.search_results instanceof Array) {
    console.log('toolDuplicateRequest.search_results[0]', props.toolDuplicateRequest.search_results[0]);
    console.log('Object.entries(toolDuplicateRequest.search_results[0])', Object.entries(props.toolDuplicateRequest.search_results[0]));
}
console.log('typeof toolDuplicateRequest.search_results', typeof props.toolDuplicateRequest.search_results);
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
                        <ul v-if="sortedSearchResults.length" class="mt-4 space-y-4">
                            <li v-for="(result, index) in sortedSearchResults.slice(0, 5)" :key="index" class="border-b pb-4">
                                <p class="font-semibold">{{ truncateTitle(result.keep.title) }}</p>
                                <p class="text-sm text-gray-600">Number of duplicates to delete: {{ result.delete.length }}</p>
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-gray-600">No search results available.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
