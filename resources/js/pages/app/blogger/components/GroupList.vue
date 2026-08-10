<script lang="ts" setup>
import BloggerEntityList from '@/components/blogger/BloggerEntityList.vue';
import GroupListItem from '@/components/blogger/GroupListItem.vue';
import type { AdminGroup as Group } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    groups: Group[];
    canCreate: boolean;
}>();

const emit = defineEmits<{
    create: [];
}>();

const { t } = useI18n();

const emptyState = computed(() => ({
    emptyText: t('blogger.groups.empty'),
    emptyCta: t('blogger.groups.empty_cta'),
    limitReachedHint: t('blogger.groups.limit_reached_hint'),
}));

function handleCreate(): void {
    emit('create');
}
</script>

<template>
    <BloggerEntityList :can-create="props.canCreate" :empty-state="emptyState" :items="props.groups" @create="handleCreate">
        <template #default="{ item }">
            <GroupListItem :item="item" />
        </template>
    </BloggerEntityList>
</template>
