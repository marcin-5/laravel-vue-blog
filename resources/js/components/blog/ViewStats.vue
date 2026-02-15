<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { useI18n } from 'vue-i18n';

defineProps({
    consented: {
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
        <Badge class="gap-1.5 px-2.5 py-1" variant="rounded1">
            <span class="inline-block h-2 w-2 rounded-full bg-primary-foreground"></span>
            <span>{{ t('blog.post.consented_views') }} {{ consented.toLocaleString() }}</span>
        </Badge>
        <Badge v-if="anonymous" class="gap-1.5 px-2.5 py-1" variant="rounded2">
            <span class="inline-block h-2 w-2 rounded-full bg-secondary-foreground"></span>
            <span>{{ t('blog.post.anonymous_views') }} {{ anonymous.toLocaleString() }}</span>
        </Badge>
        <Badge v-if="bots" class="gap-1.5 px-2.5 py-1" variant="rounded3">
            <span class="inline-block h-2 w-2 rounded-full bg-foreground"></span>
            <span>{{ t('blog.post.bot_views') }} {{ bots.toLocaleString() }}</span>
        </Badge>
    </div>
</template>
