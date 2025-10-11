<script lang="ts" setup>
defineProps<{ content: string; author: string }>();
</script>

<template>
    <main class="min-w-0 flex-1">
        <article class="prose max-w-none" v-html="content" />
    </main>
</template>

<style scoped></style>
