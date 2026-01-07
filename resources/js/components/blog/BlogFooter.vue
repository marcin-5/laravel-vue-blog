<script lang="ts" setup>
const props = defineProps<{
    html: string;
}>();
</script>

<template>
    <section v-if="props.html" class="mt-6 origin-left">
        <div
            :style="{
                fontSize: 'calc(var(--blog-footer-scale) * 1em)',
                fontFamily: 'var(--blog-footer-font)',
            }"
            class="prose max-w-none text-primary/90"
            v-html="props.html"
        />
    </section>
</template>
