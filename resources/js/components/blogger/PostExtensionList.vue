<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { ChevronDown, ChevronUp, Trash2 } from '@lucide/vue';

interface Extension {
    id: number;
    title: string;
    excerpt?: string | null;
    display_order: number;
}

const props = defineProps<{
    extensions: Extension[];
    processing: boolean;
    emptyLabel: string;
    moveUpLabel: string;
    moveDownLabel: string;
    detachLabel: string;
}>();

const emit = defineEmits<{
    move: [index: number, direction: -1 | 1];
    detach: [extension: Extension];
}>();
</script>

<template>
    <p v-if="props.extensions.length === 0" class="text-sm text-muted-foreground">{{ props.emptyLabel }}</p>
    <ol v-else class="divide-y divide-sidebar-border/70">
        <li v-for="(extension, index) in props.extensions" :key="extension.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
            <div class="min-w-0 flex-1">
                <p class="font-medium">{{ extension.title }}</p>
                <p v-if="extension.excerpt" class="truncate text-sm text-muted-foreground">{{ extension.excerpt }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <Button
                    :aria-label="props.moveUpLabel"
                    :disabled="props.processing || index === 0"
                    size="icon"
                    type="button"
                    variant="ghost"
                    @click="emit('move', index, -1)"
                >
                    <ChevronUp />
                </Button>
                <Button
                    :aria-label="props.moveDownLabel"
                    :disabled="props.processing || index === props.extensions.length - 1"
                    size="icon"
                    type="button"
                    variant="ghost"
                    @click="emit('move', index, 1)"
                >
                    <ChevronDown />
                </Button>
                <Button
                    :aria-label="props.detachLabel"
                    :disabled="props.processing"
                    size="icon"
                    type="button"
                    variant="ghost"
                    @click="emit('detach', extension)"
                >
                    <Trash2 class="text-destructive" />
                </Button>
            </div>
        </li>
    </ol>
</template>
