<template>
    <h1 v-if="level === 1" class="text-3xl font-bold text-primary mb-4">
        <slot></slot>
    </h1>
    <h2 v-else-if="level === 2" class="text-2xl font-bold text-primary mb-3">
        <slot></slot>
    </h2>
    <h3 v-else-if="level === 3" class="text-xl font-semibold text-primary mb-2">
        <slot></slot>
    </h3>
    <h4 v-else-if="level === 4" class="text-lg font-semibold">
        <slot></slot>
    </h4>
</template>

<script setup>
defineProps({
    level: {
        type: Number,
        required: true,
        validator: (value) => [1, 2, 3, 4].includes(value)
    }
});
</script>
