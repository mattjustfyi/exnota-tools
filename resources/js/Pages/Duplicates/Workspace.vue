<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import NotionObjectTitle from '@/Components/NotionObjectTitle.vue';
import Heading from '@/Components/Heading.vue';

console.log('Script setup started');

const props = defineProps({
    workspace: {
        type: Object,
        required: true
    },
    existing_duplicate_requests: {
        type: Object,
        required: true
    }
});

console.log('Props:', props);

const refreshInterval = ref(null);
const selectedDatabase = ref(null);

const selectDatabase = (database) => {
    console.log('selectDatabase called with:', database);
    selectedDatabase.value = database;
    console.log('Selected database:', database);
    callDuplicateCreateController(database.id);
};

const callDuplicateCreateController = (notionObjectId) => {
    console.log('callDuplicateCreateController called with:', notionObjectId);
    router.post('/duplicates/request', { notion_object_id: notionObjectId });
};

const fetchUserData = () => {
    console.log('fetchUserData called');
    router.post(`/test/workspace/${props.workspace.id}/refresh`);
};

const startPeriodicRefresh = () => {
    if (refreshInterval.value) {
        console.log('Periodic refresh already started. Returning.');
        return;
    }
    console.log('startPeriodicRefresh called');
    const val = setInterval(() => {
        // console.log('Periodic refresh triggered');
        router.reload();
    }, 4000);
    refreshInterval.value = val;
    console.log('Periodic refresh started with refreshInterval.value:', refreshInterval.value, 'and val:', val);
};

const stopPeriodicRefresh = () => {
    console.log('stopPeriodicRefresh called');
    if (refreshInterval.value) {
        console.log('Clearing refresh interval: refreshInterval.value:', refreshInterval.value);
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
        console.log('Periodic refresh stopped');
    }
};

const sortedNotionObjects = computed(() => {
    // console.log('sortedNotionObjects computed property called');
    if (props.workspace.notion_objects) {
        return [...props.workspace.notion_objects].sort((a, b) => {
            const dateA = new Date(a.data.createdTime.date);
            const dateB = new Date(b.data.createdTime.date);
            return dateB - dateA; // Sort in descending order (newest first)
        });
    }
    return [];
});

watch(() => props.workspace.sync_status, (newStatus) => {
    console.log('watch callback triggered. New sync_status:', newStatus);
    if (newStatus === 'queued' || newStatus === 'in_progress') {
        console.log('Starting periodic refresh');
        startPeriodicRefresh();
    } else {
        console.log('Stopping periodic refresh');
        stopPeriodicRefresh();
    }
});

onMounted(() => {
    console.log('onMounted hook called with sync_status value:', props.workspace.sync_status);
    if (props.workspace.sync_status === 'queued' || props.workspace.sync_status === 'in_progress') {
        console.log('Starting periodic refresh');
        startPeriodicRefresh();
    }
});

onUnmounted(() => {
    console.log('onUnmounted hook called');
    stopPeriodicRefresh();
});

console.log('Script setup completed');
</script>

<template>
    <AppLayout>

        <Head title="Workspace" />

        <div class="bg-background">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-background overflow-hidden p-6">

                    <div class="mb-6">
                        <Heading :level="1" class="truncate">
                            Select database
                        </Heading>
                    </div>
                    <div class="mb-6 flex flex-wrap justify-between items-center">
                        <Heading :level="4" class="truncate  mb-0">
                            Workspace: <img v-if="props.workspace.workspace_icon" :src="props.workspace.workspace_icon"
                                alt="Workspace Icon" class="inline-block mr-1"
                                style="width:1em;height:1em;vertical-align:-0.15em;" />
                            {{ workspace.workspace_name }}
                        </Heading>
                        <button @click="fetchUserData" class="btn btn-secondary btn-outline sm:mt-0">
                            Refresh
                        </button>
                    </div>
                    <!-- TODO: Update this style -->
                    <p v-if="props.workspace.sync_status === 'failed'" class="ml-4 text-red-500">
                        Sync failed. Please try again.
                    </p>

                    <div class="mt-8">
                        <h4 class="text-2xl font-bold text-gray-900">Databases:</h4>
                        <div v-if="['queued', 'in_progress'].includes(props.workspace.sync_status)"
                            class="mt-4 flex justify-center items-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500">
                            </div>
                        </div>
                        <template v-if="['complete', 'failed'].includes(props.workspace.sync_status)">
                            <ul v-if="sortedNotionObjects.length" class="mt-4 space-y-2">
                                <li v-for="object in sortedNotionObjects" :key="object.id"
                                    class="p-4 bg-gray-100 rounded flex justify-between items-center">
                                    <div class="font-bold truncate mr-4">
                                        <NotionObjectTitle :notion-object="object" />
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
                            <p v-else class="mt-4 text-gray-600">No Notion Objects available for
                                this workspace.</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
