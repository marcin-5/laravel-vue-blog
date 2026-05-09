<script lang="ts" setup>
import { useI18n } from 'vue-i18n';

type Props = {
    canSkip?: boolean;
    skips?: number;
    maxSkips?: number;
    stage: number;
    part: number;
    description: string;
};

const props = withDefaults(defineProps<Props>(), {
    canSkip: false,
    skips: 0,
    maxSkips: 0,
});

const { t } = useI18n();
</script>

<template>
    <div class="mb-6">
        <h2 class="mb-4 text-xl font-bold text-foreground">{{ t('stage') }} {{ stage }}: {{ t('part') }} {{ part }}</h2>
        <div class="flex justify-between text-muted-foreground">
            <p>{{ description }}</p>
            <p v-if="props.canSkip && props.maxSkips - props.skips > 0">
                {{ t('skips_remaining', { count: props.maxSkips - props.skips }) }}
            </p>
        </div>
    </div>
</template>
