<script lang="ts" setup>
import { useI18n } from 'vue-i18n';
import type { BlogStats } from '@/types/stats';
import { BookOpen, Calendar, Eye, Mail } from 'lucide-vue-next';

const { t } = useI18n();

defineProps<{
    stats: BlogStats[];
}>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <template v-for="blog in stats" :key="blog.id">
            <div class="col-span-full mt-4 mb-2 first:mt-0">
                <h3 class="text-sm font-medium text-muted-foreground">{{ blog.name }}</h3>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-sidebar p-4 shadow-sm dark:border-sidebar-border">
                <div class="flex items-center gap-2">
                    <BookOpen class="h-4 w-4 text-muted-foreground" />
                    <span class="text-xs font-medium tracking-wider text-muted-foreground uppercase">{{ t('blogger.stats.posts') }}</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-bold">{{ blog.posts_count }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-sidebar p-4 shadow-sm dark:border-sidebar-border">
                <div class="flex items-center gap-2">
                    <Eye class="h-4 w-4 text-muted-foreground" />
                    <span class="text-xs font-medium tracking-wider text-muted-foreground uppercase">{{ t('blogger.stats.views') }}</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-bold">{{ blog.total_views }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-sidebar p-4 shadow-sm dark:border-sidebar-border">
                <div class="flex items-center gap-2">
                    <Mail class="h-4 w-4 text-muted-foreground" />
                    <span class="text-xs font-medium tracking-wider text-muted-foreground uppercase">{{ t('blogger.stats.daily_subs') }}</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-bold">{{ blog.daily_subscriptions_count }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-sidebar p-4 shadow-sm dark:border-sidebar-border">
                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <span class="text-xs font-medium tracking-wider text-muted-foreground uppercase">{{ t('blogger.stats.weekly_subs') }}</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-bold">{{ blog.weekly_subscriptions_count }}</span>
                </div>
            </div>
        </template>

        <div v-if="stats.length === 0" class="col-span-full py-8 text-center text-muted-foreground">
            {{ t('blogger.stats.no_active_blogs') }}
        </div>
    </div>
</template>
