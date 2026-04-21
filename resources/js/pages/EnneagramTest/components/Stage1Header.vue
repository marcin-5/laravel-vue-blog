<script lang="ts" setup>
defineProps({
    part: {
        type: Number,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="mb-6">
        <h2 class="mb-4 text-xl font-bold text-foreground">Etap 1: Część {{ part }}</h2>
        <p class="text-muted-foreground">{{ description }}</p>
    </div>
</template>
