<script lang="ts" setup>
import BorderDivider from '@/components/blog/BorderDivider.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowDown } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

withDefaults(
    defineProps<{
        targetId?: string;
    }>(),
    {
        targetId: 'posts-list',
    },
);

const { t } = useI18n();
</script>

<template>
    <div class="mb-4 flex flex-col space-y-2 text-sm text-muted-foreground">
        <BorderDivider />
        <Link :href="`#${targetId}`" class="flex items-center gap-2 hover:text-primary">
            <ArrowDown class="size-4" />
            {{ t('blog.scroll_to_posts_list') }}
        </Link>
        <BorderDivider />
    </div>
</template>
