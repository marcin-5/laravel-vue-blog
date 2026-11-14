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

    router.get(
        route('dashboard'),
        selectedBloggerId.value ? { blogger_id: selectedBloggerId.value } : {},
        {
            only: ['fontUsageStats'],
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
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
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="py-2 pr-4 font-medium">{{ t('admin.stats.font_usage.font_family') }}</th>
                                <th class="py-2 text-right font-medium">{{ t('admin.stats.font_usage.blog_count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="font in stats.fonts" :key="font.value" class="border-b last:border-0">
                                <td class="py-2 pr-4">{{ font.label }}</td>
                                <td class="py-2 text-right">
                                    <span :class="font.count === 0 ? 'font-semibold text-amber-700 dark:text-amber-400' : 'font-medium'">
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
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </TooltipProvider>
        </CardContent>
    </Card>
</template>
