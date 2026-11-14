<script lang="ts" setup>
import FilterSelect from '@/components/stats/FilterSelect.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { FontUsageStats } from '@/types/stats';
import { router } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref, shallowRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    stats: FontUsageStats;
}>();

const { t } = useI18n();
const selectedBloggerId = ref<number | undefined>(props.stats.selectedBloggerId ?? undefined);
const sortByUsage = shallowRef(false);

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

const displayedFonts = computed(() =>
    sortByUsage.value ? [...props.stats.fonts].sort((first, second) => second.count - first.count) : props.stats.fonts,
);
</script>

<template>
    <Card>
        <CardHeader class="gap-4 sm:flex-row sm:items-center sm:justify-between">
            <CardTitle>{{ t('admin.stats.font_usage.title') }}</CardTitle>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <FilterSelect
                    :label="t('admin.stats.font_usage.blogger_filter')"
                    :model-value="selectedBloggerId"
                    :options="bloggerOptions"
                    :placeholder="t('admin.stats.font_usage.all_bloggers')"
                    min-width="min-w-48"
                    @update:model-value="updateBloggerFilter"
                />
                <label class="flex flex-col gap-1 text-xs text-muted-foreground">
                    {{ t('admin.stats.font_usage.sort_label') }}
                    <select v-model="sortByUsage" class="h-9 min-w-48 rounded-md border border-input bg-background px-3 text-sm text-foreground">
                        <option :value="false">{{ t('admin.stats.font_usage.sort_default') }}</option>
                        <option :value="true">{{ t('admin.stats.font_usage.sort_usage') }}</option>
                    </select>
                </label>
            </div>
        </CardHeader>
        <CardContent>
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
