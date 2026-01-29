<script lang="ts" setup>
const props = defineProps<{
    html: string;
}>();
</script>

<template>
    <section v-if="props.html" class="mt-6 origin-left">
        <div
            :style="{
                fontFamily: 'var(--blog-footer-font)',
                '--blog-body-scale': 'var(--blog-footer-scale)',
                '--blog-header-scale': 'var(--blog-footer-scale)',
                '--blog-motto-scale': 'var(--blog-footer-scale)',
                fontSize: 'calc(1rem * var(--blog-footer-scale))',
            }"
            class="prose max-w-none text-foreground/90"
            v-html="props.html"
        />
    </section>
</template>
