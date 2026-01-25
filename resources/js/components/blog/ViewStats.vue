<script lang="ts" setup>
import { useI18n } from 'vue-i18n';

defineProps({
    total: {
        type: Number,
        required: true,
    },
    unique: {
        type: Number,
        required: false,
        default: undefined,
    },
});

const { t } = useI18n();
</script>

<template>
    <div class="inline-flex items-center gap-2 rounded-full bg-card px-3 py-1 text-xs font-medium text-secondary-foreground">
        <span class="inline-block h-2 w-2 rounded-full bg-foreground opacity-80"></span>
        <span>
            {{ t('blog.post.views') }} {{ total.toLocaleString() }}
            <template v-if="unique !== undefined && unique > 0 && unique !== total">
                ({{ t('blog.post.unique_views') }} {{ Number(unique).toLocaleString() }})</template
            >
        </span>
    </div>
</template>
