<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import TestProgressBar from './TestProgressBar.vue';

type Props = {
    canSkip?: boolean;
    skips?: number;
    maxSkips?: number;
    stage: number;
    part: number;
    description: string;
    currentQuestionNumber?: number;
    minQuestionsStandard?: number;
    maxQuestionsStandard?: number;
    totalPoolSize?: number;
    leads?: any[];
};

const props = withDefaults(defineProps<Props>(), {
    canSkip: false,
    skips: 0,
    maxSkips: 0,
});

const { t } = useI18n();

const remainingSkips = computed(() => props.maxSkips - props.skips);
const shouldShowRemainingSkips = computed(() => props.canSkip && remainingSkips.value > 0);
</script>

<template>
    <div class="mx-1 mb-6 px-2 md:px-3 lg:px-4">
        <h2 class="mb-4 font-quicksand text-xl font-bold text-primary">{{ t('stage') }} {{ stage }}: {{ t('part') }} {{ part }}</h2>

        <div v-if="currentQuestionNumber !== undefined && maxQuestionsStandard !== undefined && totalPoolSize !== undefined" class="mb-6">
            <TestProgressBar
                :current="currentQuestionNumber"
                :max="maxQuestionsStandard"
                :min="minQuestionsStandard"
                :part="part"
                :stage="stage"
                :total="totalPoolSize"
                :leads="leads"
            />
        </div>

        <div class="justify-between text-muted-foreground md:flex">
            <p>{{ description }}</p>
            <p v-if="shouldShowRemainingSkips">
                {{ t('skips_remaining', { count: remainingSkips }) }}
            </p>
        </div>
    </div>
</template>
