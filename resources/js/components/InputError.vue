<script lang="ts" setup>
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="mt-1 text-sm text-destructive">
            {{ message }}
        </p>
    </div>
</template>
