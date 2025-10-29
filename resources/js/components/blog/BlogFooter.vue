<script lang="ts" setup>
const props = defineProps<{
    html: string;
}>();
</script>

<template>
    <section v-if="props.html" class="mt-6" style="font-family: 'Roboto', sans-serif">
        <div class="prose max-w-none" v-html="props.html" />
    </section>
</template>
