<script lang="ts" setup>
import FilterSelect from '@/components/stats/FilterSelect.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { FontUsageStats } from '@/types/stats';
import { router } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    stats: FontUsageStats;
}>();

const { t } = useI18n();
const selectedBloggerId = ref<number | undefined>(props.stats.selectedBloggerId ?? undefined);

const bloggerOptions = computed(() => [
    { value: 'all', label: t('admin.stats.font_usage.all_bloggers') },
    ...props.stats.bloggers.map((blogger) => ({ value: blogger.id, label: blogger.name })),
]);

watch(
    () => props.stats.selectedBloggerId,
    (bloggerId) => {
        selectedBloggerId.value = bloggerId ?? undefined;
    },
);

function updateBloggerFilter(value: AcceptableValue | undefined): void {
    selectedBloggerId.value = typeof value === 'number' ? value : undefined;

    router.get(route('dashboard'), selectedBloggerId.value ? { blogger_id: selectedBloggerId.value } : {}, {
        only: ['fontUsageStats'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <Card>
        <CardHeader class="gap-4 sm:flex-row sm:items-center sm:justify-between">
            <CardTitle>{{ t('admin.stats.font_usage.title') }}</CardTitle>
            <FilterSelect
                :label="t('admin.stats.font_usage.blogger_filter')"
                :model-value="selectedBloggerId"
                :options="bloggerOptions"
                :placeholder="t('admin.stats.font_usage.all_bloggers')"
                min-width="min-w-48"
                @update:model-value="updateBloggerFilter"
            />
        </CardHeader>
        <CardContent>
            <TooltipProvider>
                <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="font in stats.fonts" :key="font.value" class="flex items-center justify-between gap-2 border-b py-2">
                        <span class="min-w-0">{{ font.label }}</span>
                        <div class="flex shrink-0 items-center">
                            <span :class="font.count === 0 ? 'font-semibold text-warning' : 'font-medium'">
                                {{ font.count }}
                            </span>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <button
                                        :aria-label="t('admin.stats.font_usage.blog_list')"
                                        class="ml-2 inline-flex size-6 items-center justify-center rounded-full text-muted-foreground hover:bg-muted"
                                        type="button"
                                    >
                                        <Info class="size-4" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <ul v-if="font.blogs.length" class="max-w-xs space-y-1">
                                        <li v-for="(blogName, index) in font.blogs" :key="index">{{ blogName }}</li>
                                    </ul>
                                    <p v-else>{{ t('admin.stats.font_usage.unused') }}</p>
                                </TooltipContent>
                            </Tooltip>
                        </div>
                    </div>
                </div>
            </TooltipProvider>
        </CardContent>
    </Card>
</template>
