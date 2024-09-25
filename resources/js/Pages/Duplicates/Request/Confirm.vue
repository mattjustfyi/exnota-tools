<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import ProgressBar from '@/Components/ProgressBar.vue';

const props = defineProps({
    toolDuplicateRequest: {
        type: Object,
        required: true
    },
});

const searchInProgress = computed(() => {
    return !props.toolDuplicateRequest.search_ended_at;
});

const searchStatus = computed(() => {
    if (!props.toolDuplicateRequest.search_started_at) {
        return 'queued';
    }
    if (!props.toolDuplicateRequest.search_ended_at) {
        return 'in_progress';
    }
    return 'completed';
});

const searchProgress = computed(() => {
    if (!props.toolDuplicateRequest.search_results) {
        return 0;
    }
    const count = props.toolDuplicateRequest.search_results.length;
    // Using a logarithmic scale to slow down progress as count increases
    // We'll cap it at 99% to indicate it's not fully complete until search_ended_at is set
    return Math.min(Math.round((Math.log(count + 1) / Math.log(1000)) * 100), 99);
});

const searchResultsCount = computed(() => {
    return props.toolDuplicateRequest.search_results ? props.toolDuplicateRequest.search_results.length : 0;
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

const sortedDuplicates = computed(() => {
    if (props.toolDuplicateRequest.duplicates && Array.isArray(props.toolDuplicateRequest.duplicates)) {
        return [...props.toolDuplicateRequest.duplicates].sort((a, b) => b.delete.length - a.delete.length);
    }
    return [];
});

const notionObjectTitle = computed(() => {
    return truncateTitle(props.toolDuplicateRequest.notion_object?.name || '');
});

const totalDuplicates = computed(() => {
    return sortedDuplicates.value.reduce((total, result) => total + result.delete.length, 0);
});

const refreshInterval = ref(null);

const deleteInProgress = computed(() => {
    return props.toolDuplicateRequest.delete_batch_id && !props.toolDuplicateRequest.delete_ended_at;
});

const deletionFinished = computed(() => {
    return props.toolDuplicateRequest.delete_batch_id && props.toolDuplicateRequest.delete_ended_at;
});

const undeleteablePages = computed(() => {
    if (!props.toolDuplicateRequest.delete_list || !props.toolDuplicateRequest.delete_results || !props.toolDuplicateRequest.duplicates) {
        return [];
    }

    const deleteSet = new Set(props.toolDuplicateRequest.delete_list);
    const deletedSet = new Set(props.toolDuplicateRequest.delete_results);

    const undeleteableIds = [...deleteSet].filter(id => !deletedSet.has(id));

    return props.toolDuplicateRequest.duplicates.flatMap(result =>
        result.delete.filter(page => undeleteableIds.includes(page.notion_id))
            .map(page => ({
                name: page.title,
                url: page.url
            }))
    );
});

const deleteProgress = computed(() => {
    if (!props.toolDuplicateRequest.delete_list || !props.toolDuplicateRequest.delete_results) {
        return 0;
    }
    const totalItems = props.toolDuplicateRequest.delete_list.length;
    const deletedItems = props.toolDuplicateRequest.delete_results.length;
    return totalItems > 0 ? (deletedItems / totalItems) * 100 : 0;
});

const deletionStatus = computed(() => {
    if (!props.toolDuplicateRequest.delete_list || !props.toolDuplicateRequest.delete_results) {
        return { deletedItems: 0, totalItems: 0 };
    }
    return {
        deletedItems: props.toolDuplicateRequest.delete_results.length,
        totalItems: props.toolDuplicateRequest.delete_list.length
    };
});

const shouldRefresh = computed(() => {
    return deleteInProgress.value || searchInProgress.value;
});

const startPeriodicRefresh = () => {
    refreshInterval.value = setInterval(() => {
        router.reload();
    }, 5000);
};

const stopPeriodicRefresh = () => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
};

watch(() => shouldRefresh.value, (newValue) => {
    if (newValue) {
        startPeriodicRefresh();
    } else {
        stopPeriodicRefresh();
    }
});

onMounted(() => {
    if (shouldRefresh.value) {
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
                            <button v-if="!searchInProgress.value" @click="searchAgain" :disabled="deleteInProgress" :class="[
                                'px-4 py-2 text-white rounded mr-2',
                                deleteInProgress ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600'
                            ]">
                                Search again
                            </button>
                            <button v-if="!searchInProgress.value && sortedDuplicates.length > 0"
                                @click="() => router.post(route('duplicates.delete-request', toolDuplicateRequest.id))"
                                :disabled="!!toolDuplicateRequest.delete_batch_id" :class="[
                                    'px-4 py-2 text-white rounded',
                                    toolDuplicateRequest.delete_batch_id ? 'bg-gray-400 cursor-not-allowed' : 'bg-red-500 hover:bg-red-600'
                                ]">
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
                        <div v-if="deleteInProgress" class="mt-4">
                            <ProgressBar :progress="deleteProgress" />
                            <p v-if="toolDuplicateRequest.delete_started_at" class="mt-2 text-center text-gray-600">
                                Deleting duplicates... {{ deletionStatus.deletedItems }} of {{ deletionStatus.totalItems
                                }}
                                completed
                            </p>
                            <p v-else class="mt-2 text-center text-gray-600">
                                Queued
                            </p>
                        </div>
                        <template v-else-if="searchInProgress">
                            <div v-if="searchStatus === 'queued'" class="mt-4 text-center text-gray-600">
                                Search queued
                            </div>
                            <div v-else-if="searchStatus === 'in_progress'" class="mt-4">
                                <ProgressBar :progress="searchProgress" />
                                <p class="mt-2 text-center text-gray-600">
                                    Searching for duplicates...
                                    <template v-if="searchResultsCount > 0">
                                        ({{ searchResultsCount }} found so far)
                                    </template>
                                </p>
                            </div>
                        </template>
                        <template v-else-if="deletionFinished">
                            <h4 class="text-lg font-medium text-gray-900 mt-4">Pages that could not be deleted:</h4>
                            <ul v-if="undeleteablePages.length" class="mt-4 space-y-4">
                                <li v-for="(page, index) in undeleteablePages" :key="index" class="border-b pb-4">
                                    <p class="font-semibold">{{ truncateTitle(page.name || '') }}</p>
                                    <a :href="page.url" target="_blank"
                                        class="text-sm text-blue-600 hover:underline">View in Notion</a>
                                </li>
                            </ul>
                            <p v-else class="mt-4 text-gray-600">All pages were successfully deleted.</p>
                        </template>
                        <template v-else>
                            <ul v-if="sortedDuplicates.length" class="mt-4 space-y-4">
                                <li v-for="(result, index) in sortedDuplicates.slice(0, 5)" :key="index"
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
