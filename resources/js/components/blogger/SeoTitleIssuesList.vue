<script lang="ts" setup>
import type { SeoTitleIssue } from '@/types/stats';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    issues: SeoTitleIssue[];
}>();

const { t } = useI18n();
</script>

<template>
    <section class="rounded-xl border border-sidebar-border/70 p-4">
        <h2 class="text-lg font-semibold">{{ t('blogger.seo_title_issues.title') }}</h2>
        <p class="mt-1 text-sm text-muted-foreground">{{ t('blogger.seo_title_issues.description') }}</p>

        <p v-if="props.issues.length === 0" class="mt-4 text-sm text-muted-foreground">
            {{ t('blogger.seo_title_issues.empty') }}
        </p>
        <ul v-else class="mt-4 space-y-3">
            <li
                v-for="issue in props.issues"
                :key="`${issue.type}-${issue.blog_id}-${issue.post_id ?? 'blog'}`"
                class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-muted/50 p-3"
            >
                <div class="min-w-0">
                    <p class="text-sm font-medium">{{ issue.blog_name }} · {{ t(`blogger.seo_title_issues.${issue.type}`) }}</p>
                    <p class="truncate text-sm">{{ issue.title }}</p>
                    <p class="text-xs text-muted-foreground">{{ issue.length }} {{ t('blogger.seo_title_issues.length') }}</p>
                </div>
                <Link :href="issue.edit_url" class="text-sm font-medium text-primary underline underline-offset-4">
                    {{ t('blogger.seo_title_issues.edit') }}
                </Link>
            </li>
        </ul>
    </section>
</template>
