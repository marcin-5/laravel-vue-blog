<script lang="ts" setup>
import { handleContentClick } from '@/utils/domUtils';
import { blogBodyTextStyle } from '@/utils/blogStyles';

defineProps<{ content: string; author: string }>();
</script>

<template>
    <main class="min-w-0 flex-1">
        <article :style="blogBodyTextStyle" class="prose max-w-none text-primary" @click="handleContentClick" v-html="content" />
    </main>
</template>

<style scoped></style>
