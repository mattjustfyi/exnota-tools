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

const workspaceId = computed(() => {
    return props.toolDuplicateRequest.notion_object?.workspace_id;
});

const goToWorkspace = () => {
    if (workspaceId.value) {
        router.visit(`/duplicates/workspace/${workspaceId.value}`);
    }
};

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

const uiState = computed(() => {
    if (!props.toolDuplicateRequest.search_ended_at) {
        return 'SEARCH_IN_PROGRESS';
    } else if (props.toolDuplicateRequest.delete_batch_id && !props.toolDuplicateRequest.delete_ended_at) {
        return 'DELETE_IN_PROGRESS';
    } else if (props.toolDuplicateRequest.delete_batch_id && props.toolDuplicateRequest.delete_ended_at) {
        return 'DELETE_COMPLETE';
    } else {
        return 'SEARCH_COMPLETE';
    }
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
    return uiState.value === 'SEARCH_IN_PROGRESS' || uiState.value === 'DELETE_IN_PROGRESS';
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
                    <!-- Back to Workspace Link -->
                    <div class="mb-4">
                        <a @click="goToWorkspace" class="text-primary hover:text-accent cursor-pointer">
                            &larr; Back to Workspace
                        </a>
                    </div>

                    <!-- Combined Section -->
                    <div class="bg-secondary rounded-xl p-6 shadow-md">
                        <!-- Database Section -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-xl font-bold text-primary">Database: {{ notionObjectTitle }}</h3>
                                <button @click="searchAgain"
                                    :disabled="isSearchButtonDisabled || uiState === 'SEARCH_IN_PROGRESS' || uiState === 'DELETE_IN_PROGRESS'"
                                    :class="[
                                        'px-4 py-2 text-secondary rounded-lg',
                                        isSearchButtonDisabled || uiState === 'SEARCH_IN_PROGRESS' || uiState === 'DELETE_IN_PROGRESS' ? 'bg-accent cursor-not-allowed' : 'bg-primary hover:bg-accent'
                                    ]">
                                    Search again
                                </button>
                            </div>
                            <p class="text-sm text-gray-400">Searched at: {{ formatSearchedAt(toolDuplicateRequest.search_started_at) }}
                                <span @click="toggleTooltip"
                                    class="cursor-pointer text-gray-500 hover:text-gray-700 ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span ref="tooltip"
                                    class="absolute mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-2 text-xs text-gray-700 w-48 z-10 tooltip">
                                    {{ formatExactDateTime(toolDuplicateRequest.search_started_at) }}
                                </span>
                            </p>
                        </div>

                        <!-- Search Results Section -->
                        <div class="mb-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                <div class="grid grid-cols-2 gap-4 mb-4 md:mb-0">
                                    <div>
                                        <p class="text-sm text-gray-500">Total Rows</p>
                                        <p class="text-2xl font-bold text-primary">{{ searchResultsCount }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Total Duplicates</p>
                                        <p class="text-2xl font-bold text-primary">{{ duplicatesCount }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 md:mt-0">
                                    <button
                                        v-if="uiState !== 'SEARCH_IN_PROGRESS' && duplicatesCount > 0 && uiState !== 'DELETE_COMPLETE'"
                                        @click="uiState === 'DELETE_IN_PROGRESS' ? cancelDeletion() : startDeletion()"
                                        :disabled="toolDuplicateRequest.delete_cancelled" :class="[
                                            'px-4 py-2 text-secondary rounded-lg w-full md:w-auto',
                                            toolDuplicateRequest.delete_cancelled ? 'bg-accent cursor-not-allowed' :
                                                uiState === 'DELETE_IN_PROGRESS' ? 'bg-accent hover:bg-primary' : 'bg-primary hover:bg-accent'
                                        ]">
                                        {{ uiState === 'DELETE_IN_PROGRESS' ? 'Cancel Deletion' : 'Delete Duplicates' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Deletion Results Section -->
                        <div class="mt-4">
                            <div v-if="props.toolDuplicateRequest.search_failed"
                                class="mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                                <p class="font-bold">Error</p>
                                <p>Searching failed. Check that Exnota still has access to the database and try again.
                                </p>
                            </div>
                            <div v-else-if="uiState === 'SEARCH_IN_PROGRESS'">
                                <div v-if="searchStatus === 'queued'" class="text-center text-gray-600">
                                    Search queued
                                </div>
                                <div v-else-if="searchStatus === 'in_progress'">
                                    <ProgressBar :progress="searchProgress" />
                                    <p class="mt-2 text-center text-gray-600">
                                        Searching for duplicates...
                                        <template v-if="searchResultsCount > 0">
                                            (scanned {{ searchResultsCount }} rows so far)
                                        </template>
                                    </p>
                                </div>
                            </div>
                            <div v-else-if="uiState === 'SEARCH_COMPLETE'">
                                <h4 class="text-lg font-semibold mb-3">Sample duplicates</h4>
                                <div v-if="duplicatesSample.length" class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Page Title
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Number of Copies
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(result, index) in duplicatesSample" :key="index">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ truncateTitle(result.keep.title || '') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ result.delete.length + 1 }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-else class="text-gray-600">No search results available.</p>
                            </div>
                            <div v-else-if="uiState === 'DELETE_IN_PROGRESS'">
                                <ProgressBar :progress="deleteProgress" />
                                <p v-if="toolDuplicateRequest.delete_started_at" class="mt-2 text-center text-gray-600">
                                    Deleting duplicates... {{ deletionStatus.deletedItems }} of {{
                                        deletionStatus.totalItems }} completed
                                </p>
                                <p v-else class="mt-2 text-center text-gray-600">
                                    Queued for deletion
                                </p>
                            </div>
                            <div v-else-if="uiState === 'DELETE_COMPLETE'">
                                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-md mb-4">
                                    <div class="flex items-center">
                                        <svg class="h-6 w-6 text-green-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div>
                                            <p class="font-bold">
                                                Success! Deleted {{ deleteResultsCount }} duplicates.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="notDeletedCount > 0" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg shadow-md">
                                    <div class="flex items-center">
                                        <svg class="h-6 w-6 text-yellow-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <div>
                                            <p class="font-bold">
                                                Could not delete {{ notDeletedCount }} duplicates.
                                            </p>
                                            <p class="mt-1 text-sm">
                                                <a @click="searchAgain" class="text-yellow-700 underline cursor-pointer">Search again</a> to see remaining duplicates.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    .cursor-pointer:hover+.tooltip {
        display: block;
    }
}
</style>
