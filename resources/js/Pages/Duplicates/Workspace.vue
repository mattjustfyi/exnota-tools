<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    workspace: {
        type: Object,
        required: true
    },
    refreshInProgress: {
        type: Boolean,
        required: true
    },
    showResults: {
        type: Boolean,
        required: true
    },
    existing_duplicate_requests: {
        type: Object,
        required: true
    }
});

const refreshInterval = ref(null);
const selectedDatabase = ref(null);

const selectDatabase = (database) => {
    selectedDatabase.value = database;
    console.log('Selected database:', database);
    callDuplicateCreateController(database.id);
};

const callDuplicateCreateController = (notionObjectId) => {
    router.post('/duplicates/request', { notion_object_id: notionObjectId });
};

const fetchUserData = () => {
    router.post(`/test/workspace/${props.workspace.id}/refresh`);
};

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

const sortedNotionObjects = computed(() => {
    if (props.workspace.notion_objects) {
        return [...props.workspace.notion_objects].sort((a, b) => {
            const dateA = new Date(a.data.createdTime.date);
            const dateB = new Date(b.data.createdTime.date);
            return dateB - dateA; // Sort in descending order (newest first)
        });
    }
    return [];
});

watch(() => props.refreshInProgress, (newValue) => {
    if (newValue) {
        startPeriodicRefresh();
    } else {
        stopPeriodicRefresh();
    }
});

onMounted(() => {
    if (props.refreshInProgress) {
        startPeriodicRefresh();
    }
});

onUnmounted(() => {
    stopPeriodicRefresh();
});
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
                        Refresh Workspace
                    </button>

                    <div class="mt-8">
                        <h4 class="text-lg font-medium text-gray-900">Databases:</h4>
                        <div v-if="refreshInProgress" class="mt-4 flex justify-center items-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                        </div>
                        <template v-if="showResults">
                            <p v-if="sortedNotionObjects.length" class="mt-4 font-semibold">
                                Databases found: {{ sortedNotionObjects.length }}
                            </p>
                            <ul v-if="sortedNotionObjects.length" class="mt-4 space-y-2">
                                <li v-for="object in sortedNotionObjects" :key="object.id"
                                    class="p-4 bg-gray-100 rounded flex justify-between items-center">
                                    <div>
                                        <strong>{{ object.name }}</strong>
                                    </div>
                                    <button v-if="existing_duplicate_requests[object.id]"
                                        @click="router.visit(`/duplicates/request/${existing_duplicate_requests[object.id]}/confirm`)"
                                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                        Results
                                    </button>
                                    <button v-else @click="selectDatabase(object)"
                                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                        Search
                                    </button>
                                </li>
                            </ul>
                            <p v-else-if="!refreshInProgress" class="mt-4 text-gray-600">No Notion Objects available for
                                this workspace.</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
