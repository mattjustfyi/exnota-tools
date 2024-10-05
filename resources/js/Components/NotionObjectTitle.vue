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

const emoji = computed(() => {
    return props.notionObject.data.icon?.emoji?.emoji
});

const image = computed(() => {
    return props.notionObject.data.icon?.file?.url
});


</script>

<template>
    <span>
        <span v-if="emoji" class="mr-1">{{ emoji }}</span>
        <img v-else-if="image" :src="image" alt="icon" class="inline mr-1"
            style="width:1em;height:1em;vertical-align:-0.16em;" />
        {{ notionObjectTitle }}
    </span>
</template>

<style scoped>
/* .truncate-title {
    display: block;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
} */
</style>
