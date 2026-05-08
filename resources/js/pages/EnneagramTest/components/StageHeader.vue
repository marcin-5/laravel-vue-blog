<script lang="ts" setup>
import { useI18n } from 'vue-i18n';

defineProps({
    stage: {
        type: Number,
        required: true,
    },
    part: {
        type: Number,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});

const { t } = useI18n();
</script>

<template>
    <div class="mb-6">
        <h2 class="mb-4 text-xl font-bold text-foreground">{{ t('stage') }} {{ stage }}: {{ t('part') }} {{ part }}</h2>
        <p class="text-muted-foreground">{{ description }}</p>
    </div>
</template>
