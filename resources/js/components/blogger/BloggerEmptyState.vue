<script lang="ts" setup>
import { Button } from '@/components/ui/button';

interface Props {
    canCreate: boolean;
    emptyText: string;
    emptyCta: string;
    limitReachedHint: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    create: [];
}>();

function handleCreate(): void {
    emit('create');
}
</script>

<template>
    <div class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
        {{ props.emptyText }}
        <span :title="!props.canCreate ? props.limitReachedHint : ''">
            <Button :disabled="!props.canCreate" :variant="!props.canCreate ? 'muted' : 'link'" class="ml-2" type="button" @click="handleCreate">
                {{ props.emptyCta }}
            </Button>
        </span>
    </div>
</template>
