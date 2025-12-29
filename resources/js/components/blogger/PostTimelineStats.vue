<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { PostTimelineEntry } from '@/types/stats';
import { Info } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type SortOrder = 'newest' | 'oldest';

const MAX_POSTS_DISPLAYED = 5;

const VIEW_METRICS = [
    { label: 'Łącznie', key: 'total' },
    { label: 'Ostatni rok', key: 'year' },
    { label: 'Pół roku', key: 'half_year' },
    { label: 'Miesiąc', key: 'month' },
    { label: 'Tydzień', key: 'week' },
    { label: 'Dzień', key: 'day' },
] as const;

const props = defineProps<{
    posts: PostTimelineEntry[];
}>();

const currentSortOrder = ref<SortOrder>('newest');

const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString();

const sortedPosts = computed(() => {
    return [...props.posts]
        .sort((a, b) => {
            const diff = new Date(a.published_at).getTime() - new Date(b.published_at).getTime();
            return currentSortOrder.value === 'newest' ? -diff : diff;
        })
        .slice(0, MAX_POSTS_DISPLAYED);
});

function setSortOrder(order: SortOrder) {
    currentSortOrder.value = order;
}
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Oś czasu wpisów</CardTitle>
            <div class="flex gap-1">
                <Button
                    v-for="order in ['newest', 'oldest'] as const"
                    :key="order"
                    :class="{ 'bg-accent': currentSortOrder === order }"
                    class="h-7 w-auto px-2 text-xs"
                    size="icon"
                    variant="ghost"
                    @click="setSortOrder(order)"
                >
                    {{ order === 'newest' ? 'Najnowsze' : 'Najstarsze' }}
                </Button>
            </div>
        </CardHeader>
        <CardContent class="flex-1">
            <div v-if="sortedPosts.length === 0" class="flex h-full items-center justify-center text-sm text-muted-foreground">Brak wpisów</div>
            <ul v-else class="space-y-3">
                <li v-for="post in sortedPosts" :key="post.id" class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <span :title="post.title" class="truncate font-medium">{{ post.title }}</span>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <Info class="h-4 w-4 text-muted-foreground" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <div class="text-xs">
                                        <p class="mb-1 border-b pb-1 font-bold">Wyświetlenia:</p>
                                        <div class="grid grid-cols-2 gap-x-4">
                                            <template v-for="metric in VIEW_METRICS" :key="metric.key">
                                                <span>{{ metric.label }}:</span>
                                                <span class="text-right">{{ post.views[metric.key] }}</span>
                                            </template>
                                        </div>
                                    </div>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                    <span class="text-xs whitespace-nowrap text-muted-foreground">
                        {{ formatDate(post.published_at) }}
                    </span>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
