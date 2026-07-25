<script lang="ts" setup>
import GroupListItem from '@/components/blogger/GroupListItem.vue';
import GroupEmptyState from './GroupEmptyState.vue';
import type { AdminGroup as Group } from '@/types/blog.types';

const props = defineProps<{
    groups: Group[];
    canCreate: boolean;
}>();

const emit = defineEmits<{
    create: [];
}>();
</script>

<template>
    <div class="space-y-3">
        <GroupListItem v-for="group in props.groups" :key="group.id" :item="group" />

        <GroupEmptyState v-if="props.groups.length === 0" :can-create="props.canCreate" @create="emit('create')" />
    </div>
</template>
