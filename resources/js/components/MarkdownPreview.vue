<script lang="ts" setup>
interface Props {
    html: string;
    class?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div :class="['prose dark:prose-invert max-w-none overflow-auto rounded border border-border bg-muted p-4', props.class]" v-html="props.html" />
</template>
