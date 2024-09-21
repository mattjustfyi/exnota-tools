<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';

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

const searchAgain = () => {
    router.post('/duplicates/request',
        { notion_object_id: props.toolDuplicateRequest.notion_object_id },
        { replace: true });
};

const truncateTitle = (title, maxLength = 50) => {
    if (!title || title.trim() === '') return "(Untitled)";
    if (title.length <= maxLength) return title;
    return title.slice(0, maxLength) + '...';
};

const sortedSearchResults = computed(() => {
    if (props.toolDuplicateRequest.search_results && Array.isArray(props.toolDuplicateRequest.search_results)) {
        return [...props.toolDuplicateRequest.search_results].sort((a, b) => b.delete.length - a.delete.length);
    }
    return [];
});

const notionObjectTitle = computed(() => {
    return truncateTitle(props.toolDuplicateRequest.notion_object?.name || '');
});

const totalDuplicates = computed(() => {
    return sortedSearchResults.value.reduce((total, result) => total + result.delete.length, 0);
});

const refreshInterval = ref(null);

const isLoading = computed(() => {
    return props.toolDuplicateRequest.batch_id && !props.toolDuplicateRequest.delete_ended_at;
});

const startPeriodicRefresh = () => {
    refreshInterval.value = setInterval(() => {
        router.reload();
    }, 2000);
};

const stopPeriodicRefresh = () => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
};

watch(() => isLoading.value, (newValue) => {
    if (newValue) {
        startPeriodicRefresh();
    } else {
        stopPeriodicRefresh();
    }
});

onMounted(() => {
    if (isLoading.value) {
        startPeriodicRefresh();
    }
});

onUnmounted(() => {
    stopPeriodicRefresh();
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
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Search Results</h3>
                        <div>
                            <button v-if="!jobsInProgress" @click="searchAgain"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 mr-2">
                                Search again
                            </button>
                            <button v-if="!jobsInProgress && sortedSearchResults.length > 0"
                                @click="() => router.post(route('duplicates.delete-request', toolDuplicateRequest.id))"
                                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                                Delete Duplicates
                            </button>
                        </div>
                    </div>
                    <p class="mt-4 text-gray-800 font-semibold">
                        Database: {{ notionObjectTitle }}
                    </p>
                    <p class="mt-2 text-gray-700">
                        Total Duplicates: {{ totalDuplicates }}
                    </p>
                    <p class="mt-2 text-gray-600">
                        Searched At: {{ new Date(toolDuplicateRequest.search_started_at).toLocaleString() }}
                    </p>

                    <div class="mt-8">
                        <h4 class="text-lg font-medium text-gray-900">Search Results:</h4>
                        <div v-if="isLoading" class="mt-4 flex justify-center items-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                            <span class="ml-2 text-gray-600">Processing1...</span>
                        </div>
                        <template v-else-if="jobsInProgress">
                            <div class="mt-4 flex justify-center items-center">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                                <span class="ml-2 text-gray-600">Processing2...</span>
                            </div>
                        </template>
                        <template v-else>
                            <ul v-if="sortedSearchResults.length" class="mt-4 space-y-4">
                                <li v-for="(result, index) in sortedSearchResults.slice(0, 5)" :key="index"
                                    class="border-b pb-4">
                                    <p class="font-semibold">{{ truncateTitle(result.keep.title || '') }}</p>
                                    <p class="text-sm text-gray-600">Number of duplicates to delete: {{
                                        result.delete.length }}</p>
                                </li>
                            </ul>
                            <p v-else class="mt-4 text-gray-600">No search results available.</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
