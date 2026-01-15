<script lang="ts" setup>
import StatCard from '@/components/blogger/StatCard.vue';
import type { BlogStats } from '@/types/stats';
import { BookOpen, Calendar, Eye, Mail } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

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

            <StatCard :icon="BookOpen" :title="t('blogger.stats.posts')" :value="blog.posts_count" />

            <StatCard :icon="Eye" :title="t('blogger.stats.views')" :value="blog.lifetime_views" />

            <StatCard :icon="Mail" :title="t('blogger.stats.daily_subs')" :value="blog.daily_subscriptions_count" />

            <StatCard :icon="Calendar" :title="t('blogger.stats.weekly_subs')" :value="blog.weekly_subscriptions_count" />
        </template>

        <div v-if="stats.length === 0" class="col-span-full py-8 text-center text-muted-foreground">
            {{ t('blogger.stats.no_active_blogs') }}
        </div>
    </div>
</template>
