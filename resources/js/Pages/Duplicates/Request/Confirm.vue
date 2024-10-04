<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import ProgressBar from '@/Components/ProgressBar.vue';

console.log('Component setup started');

const props = defineProps({
    toolDuplicateRequest: {
        type: Object,
        required: true
    },
});

const searchResultsCount = computed(() => {
    return props.toolDuplicateRequest.search_results_count ?? 0;
});

const duplicatesCount = computed(() => {
    return props.toolDuplicateRequest.duplicates_count ?? 0;
});

const deleteListCount = computed(() => {
    return props.toolDuplicateRequest.delete_list_count ?? 0;
});

const deleteResultsCount = computed(() => {
    return props.toolDuplicateRequest.delete_results_count ?? 0;
});

const notDeletedCount = computed(() => {
    return props.toolDuplicateRequest.not_deleted_count ?? 0;
});

const duplicatesSample = computed(() => {
    return props.toolDuplicateRequest.duplicates_sample ?? [];
});

console.log('Props received:', props.toolDuplicateRequest);

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
    if (!searchResultsCount.value) {
        return 0;
    }
    const count = searchResultsCount.value;
    // Using a logarithmic scale up to 10,000 results
    // We'll cap it at 99% to indicate it's not fully complete until search_ended_at is set
    return Math.min(Math.round((Math.log(count + 1) / Math.log(10000)) * 100), 99);
});

const isSearchButtonDisabled = ref(false);

const searchAgain = () => {
    console.log('searchAgain called');
    isSearchButtonDisabled.value = true;
    router.post('/duplicates/request',
        { notion_object_id: props.toolDuplicateRequest.notion_object_id },
        {
            replace: true,
            preserveState: false,
            preserveScroll: false,
        });
};

watch(() => props.toolDuplicateRequest.search_ended_at, (newValue) => {
    console.log('search_ended_at changed:', newValue);
    if (newValue) {
        isSearchButtonDisabled.value = false;
    }
});

const truncateTitle = (title, maxLength = 50) => {
    if (!title || title.trim() === '') return "(Untitled)";
    if (title.length <= maxLength) return title;
    return title.slice(0, maxLength) + '...';
};

const notionObjectTitle = computed(() => {
    return truncateTitle(props.toolDuplicateRequest.notion_object?.name || '');
});

const formatSearchedAt = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = (now - date) / (1000 * 60 * 60);

    if (diffInHours < 24) {
        if (diffInHours < 1) {
            const minutes = Math.floor((now - date) / (1000 * 60));
            return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
        } else {
            const hours = Math.floor(diffInHours);
            return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
        }
    } else {
        return date.toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit'
        });
    }
};

const formatExactDateTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZoneName: 'short'
    });
};

const refreshInterval = ref(null);

const tooltip = ref(null);

const toggleTooltip = () => {
    if (tooltip.value) {
        tooltip.value.classList.toggle('show');
    }
};

onMounted(() => {
    document.addEventListener('click', (event) => {
        if (tooltip.value && !tooltip.value.contains(event.target) && !event.target.closest('.cursor-pointer')) {
            tooltip.value.classList.remove('show');
        }
    });
});

const deleteInProgress = computed(() => {
    return props.toolDuplicateRequest.delete_batch_id && !props.toolDuplicateRequest.delete_ended_at;
});

const deletionFinished = computed(() => {
    return props.toolDuplicateRequest.delete_batch_id && props.toolDuplicateRequest.delete_ended_at;
});

const deleteProgress = computed(() => {
    if (!deleteListCount.value || !deleteResultsCount.value) {
        return 0;
    }
    const totalItems = deleteListCount.value;
    const deletedItems = deleteResultsCount.value;
    return totalItems > 0 ? (deletedItems / totalItems) * 100 : 0;
});

const deletionStatus = computed(() => {
    if (!deleteListCount.value || !deleteResultsCount.value) {
        return { deletedItems: 0, totalItems: 0 };
    }
    return {
        deletedItems: deleteResultsCount.value,
        totalItems: deleteListCount.value
    };
});

const startDeletion = () => {
    router.post(route('duplicates.delete-request', props.toolDuplicateRequest.id));
};

const cancelDeletion = () => {
    router.post(route('duplicates.cancel-request', props.toolDuplicateRequest.id), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const shouldRefresh = computed(() => {
    return deleteInProgress.value || searchInProgress.value;
});

const startPeriodicRefresh = () => {
    console.log('Starting periodic refresh');
    refreshInterval.value = setInterval(() => {
        console.log('Reloading page');
        router.reload();
    }, 6000);
};

const stopPeriodicRefresh = () => {
    console.log('Stopping periodic refresh');
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
};

watch(() => shouldRefresh.value, (newValue) => {
    console.log('shouldRefresh changed:', newValue);
    if (newValue) {
        startPeriodicRefresh();
    } else {
        stopPeriodicRefresh();
    }
});

onMounted(() => {
    console.log('Component mounted');
    if (shouldRefresh.value) {
        startPeriodicRefresh();
    }
});

onUnmounted(() => {
    console.log('Component unmounted');
    stopPeriodicRefresh();
});

</script>

<template>
    <AppLayout>

        <Head title="Confirm Duplicate Request" />

        <div class="py-12 bg-background">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-background overflow-hidden p-6">
                    <!-- Database Overview Section -->
                    <div class="mb-8 bg-secondary rounded-xl p-6 shadow-md">
                        <h3 class="text-xl font-bold text-primary mb-4">Database Overview</h3>
                        <div class="flex items-center justify-between">
                            <p class="text-primary font-medium">View your database progress</p>
                            <div class="w-1/2">
                                <ProgressBar :progress="76" />
                            </div>
                        </div>
                    </div>

                    <!-- Database Section -->
                    <div class="mb-8 bg-secondary rounded-xl p-6 shadow-md">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-bold text-primary">Database: {{ notionObjectTitle }}</h3>
                            <button @click="searchAgain"
                                :disabled="isSearchButtonDisabled || searchInProgress || deleteInProgress" :class="[
                                    'px-4 py-2 text-secondary rounded-lg',
                                    isSearchButtonDisabled || searchInProgress || deleteInProgress ? 'bg-accent cursor-not-allowed' : 'bg-primary hover:bg-accent'
                                ]">
                                Search again
                            </button>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="bg-secondary rounded-xl p-6 shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-primary">Search Results</h3>
                            <button v-if="!searchInProgress.value && duplicatesCount > 0"
                                @click="deleteInProgress ? cancelDeletion() : startDeletion()"
                                :disabled="toolDuplicateRequest.delete_ended_at !== null || toolDuplicateRequest.delete_cancelled"
                                :class="[
                                    'px-4 py-2 text-secondary rounded-lg',
                                    toolDuplicateRequest.delete_ended_at !== null || toolDuplicateRequest.delete_cancelled ? 'bg-accent cursor-not-allowed' :
                                        deleteInProgress ? 'bg-accent hover:bg-primary' : 'bg-primary hover:bg-accent'
                                ]">
                                {{
                                    deleteInProgress ? 'Cancel Deletion' :
                                        toolDuplicateRequest.delete_cancelled ? 'Delete Cancelled' :
                                            toolDuplicateRequest.delete_ended_at !== null ? 'Deletion Completed' :
                                                'Delete Duplicates'
                                }}
                            </button>
                        </div>
                        <div class="grid grid-cols-4 gap-4 mt-4">
                            <div>
                                <p class="text-sm text-gray-500">Total Rows</p>
                                <p class="text-2xl font-bold text-primary">{{ searchResultsCount }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Duplicates</p>
                                <p class="text-2xl font-bold text-primary">{{ duplicatesCount }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Deleted</p>
                                <p class="text-2xl font-bold text-primary">{{ deleteResultsCount }}</p>
                            </div>
                            <div class="relative">
                                <p class="text-sm text-gray-500">Searched At</p>
                                <div class="flex items-center">
                                    <p class="text-2xl font-bold text-primary">
                                        {{ formatSearchedAt(toolDuplicateRequest.search_started_at) }}
                                    </p>
                                    <div class="ml-2 relative">
                                        <span @click="toggleTooltip" class="cursor-pointer text-gray-500 hover:text-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        <div ref="tooltip" class="absolute right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-2 text-sm text-gray-700 w-48 z-10 tooltip">
                                            {{ formatExactDateTime(toolDuplicateRequest.search_started_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <h4 class="text-lg font-bold text-primary">Search Results:</h4>
                            <div v-if="props.toolDuplicateRequest.search_failed"
                                class="mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                                <p class="font-bold">Error</p>
                                <p>Searching failed. Check that Exnota still has access to the database and try again.
                                </p>
                            </div>
                            <div v-else-if="deleteInProgress" class="mt-4">
                                <ProgressBar :progress="deleteProgress" />
                                <p v-if="toolDuplicateRequest.delete_started_at" class="mt-2 text-center text-gray-600">
                                    Deleting duplicates... {{ deletionStatus.deletedItems }} of {{
                                        deletionStatus.totalItems
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
                                            (scanned {{ searchResultsCount }} rows so far)
                                        </template>
                                    </p>
                                </div>
                            </template>
                            <template v-else-if="deletionFinished">
                                <h4 class="text-lg font-medium text-gray-900 mt-4">Pages that could not be deleted:</h4>
                                <p v-if="notDeletedCount > 0" class="mt-4 text-gray-600">
                                    {{ notDeletedCount }} pages were not deleted. Search again for more details.
                                </p>
                                <p v-else class="mt-4 text-gray-600">All pages were successfully deleted.</p>
                            </template>
                            <template v-else>
                                <ul v-if="duplicatesSample.length" class="mt-4 space-y-4">
                                    <li v-for="(result, index) in duplicatesSample" :key="index" class="border-b pb-4">
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
        </div>
    </AppLayout>
</template>

<style scoped>
.tooltip {
    display: none;
}

.tooltip.show {
    display: block;
}

@media (hover: hover) {
    .cursor-pointer:hover + .tooltip {
        display: block;
    }
}
</style>
