<template>
    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
        <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: `${progress}%` }"></div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    progress: {
        type: Number,
        required: true,
    },
});

const clampedProgress = computed(() => {
    return Math.min(Math.max(props.progress, 0), 100);
});
</script>
