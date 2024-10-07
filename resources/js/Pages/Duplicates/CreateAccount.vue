<script setup>
import Heading from '@/Components/Heading.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppLayoutNoUser from '@/Layouts/AppLayoutNoUser.vue';
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, onBeforeMount, ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
});

</script>

<template>
    <AppLayout>

        <Head title="Duplicates - Exnota Tools - Find/delete duplicate Notion database rows" />
        <div class="bg-background">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-background overflow-hidden p-6">


                </div>
            </div>
        </div>
    </AppLayout>
</template>
