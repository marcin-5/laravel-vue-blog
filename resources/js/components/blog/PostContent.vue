<script lang="ts" setup>
defineProps<{ content: string; author: string }>();
</script>

<template>
    <main class="min-w-0 flex-1">
        <article
            :style="{ fontFamily: 'var(--blog-body-font)', fontSize: 'calc(1rem * var(--blog-body-scale))' }"
            class="prose max-w-none"
            v-html="content"
        />
    </main>
</template>

<style scoped></style>
