<script lang="ts" setup>
import { useI18n } from 'vue-i18n';

defineProps({
    registered: {
        type: Number,
        default: 0,
    },
    anonymous: {
        type: Number,
        default: 0,
    },
    bots: {
        type: Number,
        default: 0,
    },
});

const { t } = useI18n();
</script>

<template>
    <div class="flex items-center gap-2">
        <div
            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/50 bg-emerald-500/10 px-2.5 py-1 text-xs font-medium dark:text-emerald-100"
        >
            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
            <span>{{ t('blog.post.registered_views') }} {{ registered.toLocaleString() }}</span>
        </div>
        <div
            class="inline-flex items-center gap-1.5 rounded-full border border-blue-200/50 bg-blue-500/10 px-2.5 py-1 text-xs font-medium dark:text-blue-100"
        >
            <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
            <span>{{ t('blog.post.anonymous_views') }} {{ anonymous.toLocaleString() }}</span>
        </div>
        <div
            class="inline-flex items-center gap-1.5 rounded-full border border-orange-200/50 bg-orange-500/10 px-2.5 py-1 text-xs font-medium dark:text-orange-100"
        >
            <span class="inline-block h-2 w-2 rounded-full bg-orange-500"></span>
            <span>{{ t('blog.post.bot_views') }} {{ bots.toLocaleString() }}</span>
        </div>
    </div>
</template>
