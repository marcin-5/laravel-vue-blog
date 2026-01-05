<script lang="ts" setup>
const props = defineProps<{
    html: string;
}>();
</script>

<template>
    <section v-if="props.html" :style="{ fontSize: 'var(--blog-footer-scale)em', fontFamily: 'var(--blog-footer-font)' }" class="mt-6 origin-left">
        <div class="prose max-w-none text-primary/90" v-html="props.html" />
    </section>
</template>
