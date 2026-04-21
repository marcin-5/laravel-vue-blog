<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold">Enneagram Test</h1>
        <pre>{{ testData }}</pre>
    </div>
</template>

<script lang="ts" setup>
defineProps<{
    testData: Object
}>()
</script>
