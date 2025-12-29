<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useI18nNs } from '@/composables/useI18nNs';
import type { PostPerformanceEntry } from '@/types/stats';
import { computed, ref } from 'vue';

const { t } = await useI18nNs('blogger');

type SortOrder = 'best' | 'worst';

const MAX_POSTS_DISPLAYED = 5;

const props = defineProps<{
    posts: PostPerformanceEntry[];
}>();

const currentSortOrder = ref<SortOrder>('best');

const displayPosts = computed(() => {
    return [...props.posts].sort((a, b) => (currentSortOrder.value === 'best' ? b.ratio - a.ratio : a.ratio - b.ratio)).slice(0, MAX_POSTS_DISPLAYED);
});

function setSortOrder(order: SortOrder) {
    currentSortOrder.value = order;
}
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">{{ t('blogger.stats.performance_title') }}</CardTitle>
            <span class="text-[10px] text-muted-foreground uppercase">{{ t('blogger.stats.views_per_day') }}</span>
            <div class="flex gap-1">
                <Button
                    v-for="order in ['best', 'worst'] as const"
                    :key="order"
                    :class="['h-7 w-auto px-2 text-xs', { 'bg-accent': currentSortOrder === order }]"
                    size="icon"
                    variant="ghost"
                    @click="setSortOrder(order)"
                >
                    {{ t(`blogger.stats.${order}`) }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="flex-1">
            <div v-if="displayPosts.length === 0" class="flex h-full items-center justify-center text-sm text-muted-foreground">
                {{ t('blogger.stats.no_data') }}
            </div>
            <ul v-else class="space-y-3">
                <li v-for="post in displayPosts" :key="post.id" class="flex items-center justify-between text-sm">
                    <span :title="post.title" class="truncate pr-2 font-medium">{{ post.title }}</span>
                    <div class="flex flex-col items-end">
                        <span class="font-bold">{{ post.ratio.toFixed(2) }}</span>
                    </div>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
