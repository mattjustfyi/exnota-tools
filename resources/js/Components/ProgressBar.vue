<template>
    <progress class="progress progress-primary w-full" :value="clampedProgress" max="100"></progress>
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
