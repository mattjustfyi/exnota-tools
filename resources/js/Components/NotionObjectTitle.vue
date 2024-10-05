<script setup>
import { computed } from 'vue';

const props = defineProps({
    notionObject: {
        type: Object,
        required: true
    }
});

const truncateTitle = (title) => {
    if (!title || title.trim() === '') return "(Untitled)";
    return title;
};

const notionObjectTitle = computed(() => {
    return truncateTitle(props.notionObject?.name || '');
});
</script>

<template>
    <span class="truncate-title">{{ notionObjectTitle }}</span>
</template>

<style scoped>
.truncate-title {
    display: block;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
