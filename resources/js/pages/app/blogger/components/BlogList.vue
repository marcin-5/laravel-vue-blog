<script lang="ts" setup>
import BloggerEntityList from '@/components/blogger/BloggerEntityList.vue';
import BlogListItem from '@/components/blogger/BlogListItem.vue';
import type { AdminBlog as Blog, Category } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blogs: Blog[];
    canCreate: boolean;
    categories: Category[];
}>();

const emit = defineEmits<{
    create: [];
}>();

const { t } = useI18n();

const emptyState = computed(() => ({
    emptyText: t('blogger.empty'),
    emptyCta: t('blogger.empty_cta'),
    limitReachedHint: t('blogger.limit_reached_hint'),
}));

function handleCreate(): void {
    emit('create');
}
</script>

<template>
    <BloggerEntityList :can-create="props.canCreate" :empty-state="emptyState" :items="props.blogs" @create="handleCreate">
        <template #default="{ item }">
            <BlogListItem :categories="props.categories" :item="item" />
        </template>
    </BloggerEntityList>
</template>
