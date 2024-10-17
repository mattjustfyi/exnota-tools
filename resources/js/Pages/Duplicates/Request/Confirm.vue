<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import SearchedAt from '@/Components/SearchedAt.vue';
import NotionObjectTitle from '@/Components/NotionObjectTitle.vue';
import Heading from '@/Components/Heading.vue';

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

const titleOrUndefined = (title) => {
    if (!title || title.trim() === '') return "(Untitled)";
    return title;
};

const uiState = computed(() => {
    if (!props.toolDuplicateRequest.search_ended_at) {
        return 'SEARCH_IN_PROGRESS';
    } else if (props.toolDuplicateRequest.search_failed) {
        return 'SEARCH_FAILED';
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

const refreshInterval = ref(null);

const deleteProgress = computed(() => {
    if (!deleteListCount.value || !deleteResultsCount.value) {
        return 0;
    }
    const totalItems = deleteListCount.value;
    const deletedItems = deleteResultsCount.value;
    return totalItems > 0 ? (deletedItems / totalItems) * 100 : 0;
});

const deletionStatus = computed(() => {
    return {
        deletedItems: deleteResultsCount.value ?? 0,
        totalItems: deleteListCount.value ?? 0,
    };
});

const showDeleteConfirmModal = () => {
    document.getElementById('modal-delete-confirm').showModal();
};

const startDeletion = () => {
    document.getElementById('modal-delete-confirm').close();
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

        <Head title="Delete duplicates in Notion databases - Exnota Tools" />

        <!-- Back to Workspace Link -->
        <div class="mb-4">
            <a @click="goToWorkspace" class="text-primary hover:text-accent cursor-pointer">
                &larr; Back to Workspace
            </a>
        </div>

        <!-- Database Section -->
        <div class="mb-6">
            <Heading :level="1" class="truncate">
                4/4 Delete duplicates
            </Heading>
        </div>
        <div class="mb-6 flex justify-between items-center">
            <Heading :level="4" class="truncate mr-4">
                Database:
                <NotionObjectTitle :notion-object="toolDuplicateRequest.notion_object" />
            </Heading>
        </div>

        <!-- Search In Progress Section -->
        <div v-if="uiState === 'SEARCH_IN_PROGRESS'">
            <div class="card bg-base-100">
                <div class="card-body">
                    <h2 class="card-title justify-center mb-4">Searching</h2>
                    <div>
                        <ProgressBar :progress="searchProgress" />
                        <p v-if="searchStatus === 'queued'" class="text-base-content text-center">
                            Search queued
                        </p>
                        <p v-else-if="searchResultsCount === 0" class="text-base-content text-center">
                            Starting search...
                        </p>
                        <p v-else class="text-base-content text-center">
                            Up to row {{ searchResultsCount }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Section -->
        <div v-else-if="uiState === 'SEARCH_FAILED'">
            <div class="alert alert-error mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="font-bold">Searching failed</h3>
                    <div class="text-xs mt-2">Check that Exnota still has access to the database and <a class="link"
                            @click="searchAgain">try again</a>.
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Complete Section -->
        <div v-else-if="uiState === 'SEARCH_COMPLETE'">
            <div class="card bg-base-100">
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-500">Total Rows</p>
                            <p class="text-2xl font-bold text-primary">{{ searchResultsCount }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Duplicate rows*</p>
                            <p class="text-2xl font-bold text-primary">{{ duplicatesCount }}</p>
                        </div>
                    </div>
                    <div class="flex justify-center items-center">
                        <button v-if="duplicatesCount > 0" @click="showDeleteConfirmModal()"
                            :disabled="toolDuplicateRequest.delete_cancelled" :class="[
                                'btn w-full max-w-md mx-auto',
                                toolDuplicateRequest.delete_cancelled ? 'btn-disabled' : 'btn-primary'
                            ]">
                            Delete {{ duplicatesCount }} duplicates
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mt-4 italic">* Duplicate means the same title. Other
                        properties
                        and
                        content may differ.</p>
                </div>
            </div>

            <div class="mt-6 mb-24">
                <p class="text-sm text-gray-400">
                    <SearchedAt :searched-at="toolDuplicateRequest.search_started_at" />.
                    <a @click="searchAgain" :class="[
                        'cursor-pointer underline',
                        isSearchButtonDisabled || uiState === 'SEARCH_IN_PROGRESS' || uiState === 'DELETE_IN_PROGRESS'
                            ? 'text-gray-400 cursor-not-allowed'
                            : 'text-gray-400 hover:text-gray-400'
                    ]"
                        :disabled="isSearchButtonDisabled || uiState === 'SEARCH_IN_PROGRESS' || uiState === 'DELETE_IN_PROGRESS'">
                        Search again</a>.
                </p>
            </div>

            <Heading :level="2">Sample duplicates</Heading>
            <div v-if="duplicatesSample.length" class="space-y-4">
                <a v-for="(result, index) in duplicatesSample" :key="index" :href="result.keep.url" target="_blank"
                    rel="noopener noreferrer"
                    class="card bg-base-100 no-underline hover:no-underline flex justify-between items-center">
                    <div class="card-body p-4 w-full">
                        <div class="flex">
                            <div class="flex-grow mr-6 overflow-hidden">
                                <div class="card-title">
                                    <h2 class="text-primary font-medium text-sm truncate">
                                        {{ titleOrUndefined(result.keep.title) }}
                                    </h2>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    Copies: {{ result.delete.length + 1 }}
                                </p>
                            </div>
                            <div class="flex items-center">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <p v-else class="text-gray-600">No duplicates found.</p>
        </div>

        <!-- Delete In Progress Section -->
        <div v-else-if="uiState === 'DELETE_IN_PROGRESS'">
            <div class="card bg-base-100">
                <div class="card-body">
                    <h2 class="card-title justify-center mb-4">Deleting</h2>
                    <div>
                        <ProgressBar :progress="deleteProgress" />
                        <p v-if="toolDuplicateRequest.delete_started_at" class="text-base-content text-center mt-2">
                            Deleted {{ deletionStatus.deletedItems }} of {{
                                deletionStatus.totalItems }}
                        </p>
                        <p v-else class="text-base-content text-center mt-2">
                            Queued for deletion
                        </p>
                        <div class="mt-16 text-center">
                            <button @click="cancelDeletion" class="btn btn-warning">
                                Cancel Deletion
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Complete Section -->
        <div v-else-if="uiState === 'DELETE_COMPLETE'">

            <div v-if="toolDuplicateRequest.delete_cancelled" class="alert alert-warning mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="font-bold">Cancelled after {{ deleteResultsCount }} deleted duplicates.</h3>
                    <div class="text-xs"><a class="link" @click="searchAgain">Search again</a>
                        for
                        duplicates.
                    </div>
                </div>
            </div>
            <div v-else role="alert" class="alert alert-success mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-bold">
                    Deleted {{ deleteResultsCount }} duplicates.
                </span>
            </div>
            <div v-if="notDeletedCount > 0" class="alert alert-warning mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="font-bold">Could not delete {{ notDeletedCount }} duplicates.</h3>
                    <div class="text-xs"><a class="link" @click="searchAgain">Search again</a>
                        for
                        remaining duplicates.
                    </div>
                </div>
            </div>
            <div v-if="notDeletedCount > 0" class="text-sm text-gray-500">
                <SearchedAt :searched-at="toolDuplicateRequest.delete_started_at" />.
            </div>
            <div v-else class="text-sm text-gray-500">
                <SearchedAt :searched-at="toolDuplicateRequest.delete_started_at" />.
                <a class="link" @click="searchAgain">Search database again</a>.
            </div>
        </div>
    </AppLayout>

    <!-- Delete Confirmation Modal -->
    <dialog id="modal-delete-confirm" class="modal">
        <div class="modal-box">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="font-bold text-lg">Confirm Deletion</h3>
            <p class="py-4">{{ duplicatesCount }} duplicates will be moved to your Trash in Notion. You can manually
                restore them in Notion if necessary.</p>
            <p>Duplicate means <strong>the titles are the same</strong>. Properties and content may be different.</p>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-outline mr-2">Cancel</button>
                </form>
                <button @click="startDeletion" class="btn btn-primary">Delete Duplicates</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</template>

<style scoped>
.truncate-title {
    display: block;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flex-grow {
    flex-grow: 1;
}

.flex-shrink-0 {
    flex-shrink: 0;
}

.space-y-2>*+* {
    margin-top: 0.5rem;
}
</style>
