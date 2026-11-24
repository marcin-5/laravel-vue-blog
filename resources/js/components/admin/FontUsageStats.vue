<script lang="ts" setup>
import FilterSelect from '@/components/stats/FilterSelect.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { FontUsageStats } from '@/types/stats';
import { router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Info } from '@lucide/vue';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref, shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    stats: FontUsageStats;
}>();

const { t } = useI18n();
const selectedBloggerId = ref<number | undefined>(props.stats.selectedBloggerId ?? undefined);
const sortByUsage = shallowRef(false);
const isCollapsed = shallowRef(false);

const bloggerOptions = computed(() => [
    { value: 'all', label: t('admin.stats.font_usage.all_bloggers') },
    ...props.stats.bloggers.map((blogger) => ({ value: blogger.id, label: blogger.name })),
]);

const sortOptions = computed(() => [
    { value: 'default', label: t('admin.stats.font_usage.sort_default') },
    { value: 'usage', label: t('admin.stats.font_usage.sort_usage') },
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

function updateSort(value: AcceptableValue | undefined): void {
    sortByUsage.value = value === 'usage';
}

const displayedFonts = computed(() =>
    sortByUsage.value ? [...props.stats.fonts].sort((first, second) => second.count - first.count) : props.stats.fonts,
);
</script>

<template>
    <Card>
        <CardHeader :class="isCollapsed ? 'grid-rows-1 items-center' : 'sm:flex-row sm:items-center sm:justify-between'">
            <div class="flex items-center gap-4">
                <button
                    :aria-expanded="!isCollapsed"
                    :aria-label="isCollapsed ? 'Expand font usage stats' : 'Collapse font usage stats'"
                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-muted"
                    type="button"
                    @click="isCollapsed = !isCollapsed"
                >
                    <ChevronDown v-if="isCollapsed" class="size-4" />
                    <ChevronUp v-else class="size-4" />
                </button>
                <CardTitle>{{ t('admin.stats.font_usage.title') }}</CardTitle>
            </div>
            <div v-if="!isCollapsed" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <FilterSelect
                    :label="t('admin.stats.font_usage.blogger_filter')"
                    :model-value="selectedBloggerId"
                    :options="bloggerOptions"
                    :placeholder="t('admin.stats.font_usage.all_bloggers')"
                    min-width="min-w-48"
                    @update:model-value="updateBloggerFilter"
                />
                <FilterSelect
                    :label="t('admin.stats.font_usage.sort_label')"
                    :model-value="sortByUsage ? 'usage' : 'default'"
                    :options="sortOptions"
                    min-width="min-w-48"
                    @update:model-value="updateSort"
                />
            </div>
        </CardHeader>
        <CardContent v-if="!isCollapsed">
            <TooltipProvider>
                <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="font in displayedFonts" :key="font.value" class="flex items-center justify-between gap-2 border-b py-2">
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
