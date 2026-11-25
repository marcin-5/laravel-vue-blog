<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { CaseSensitive } from '@lucide/vue';

defineProps<{
    active: boolean;
    label: string;
}>();

const emit = defineEmits<{
    (event: 'toggle'): void;
}>();
</script>

<template>
    <Button :aria-label="label" :aria-pressed="active" class="h-9 w-9" size="icon" type="button" variant="outline" @click="emit('toggle')">
        <CaseSensitive aria-hidden="true" class="size-5" />
    </Button>
</template>
