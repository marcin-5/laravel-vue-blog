<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg :class="className" v-bind="$attrs" viewBox="0 0 40 42" xmlns="http://www.w3.org/2000/svg">
        <path clip-rule="evenodd" d="" fill="currentColor" fill-rule="evenodd" />
    </svg>
</template>
