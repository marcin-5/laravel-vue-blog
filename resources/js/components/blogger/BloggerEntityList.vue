<script generic="TItem extends { id: number }" lang="ts" setup>
import BloggerEmptyState from '@/components/blogger/BloggerEmptyState.vue';

interface EmptyStateLabels {
    emptyText: string;
    emptyCta: string;
    limitReachedHint: string;
}

interface Props {
    items: TItem[];
    canCreate: boolean;
    emptyState: EmptyStateLabels;
}

const props = defineProps<Props>();

defineSlots<{
    default: (props: { item: TItem; index: number }) => unknown;
}>();

const emit = defineEmits<{
    create: [];
}>();

function handleCreate(): void {
    emit('create');
}
</script>

<template>
    <div class="space-y-3">
        <template v-for="(item, index) in props.items" :key="item.id">
            <slot :index="index" :item="item" />
        </template>

        <BloggerEmptyState v-if="props.items.length === 0" v-bind="props.emptyState" :can-create="props.canCreate" @create="handleCreate" />
    </div>
</template>
