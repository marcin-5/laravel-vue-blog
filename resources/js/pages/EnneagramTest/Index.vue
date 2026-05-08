<script lang="ts" setup>
import { computed, ref } from 'vue';
import Stage1 from './Stage1.vue';
import Stage2 from './Stage2.vue';
import Summary from './components/Summary.vue';
import { isStage1Question, isStage2Question, type QuestionIdHolder } from './composables/shared/questionIds';
import type { CompleteResults } from './composables/useEnneagramStage1';

const props = defineProps<{
    testData: any;
    appDebug?: boolean;
}>();

const currentStage = ref(1);
const stage1Results = ref<CompleteResults | null>(null);
const stage2Results = ref<any>(null);

const stage1Questions = computed(() => {
    return props.testData.questions.filter((q: QuestionIdHolder) => isStage1Question(q));
});

const stage1Config = computed(() => {
    return props.testData.testConfig.stages.stage1;
});

const stage2Questions = computed(() => {
    return props.testData.questions.filter((q: QuestionIdHolder) => isStage2Question(q));
});

const stage2Config = computed(() => {
    return props.testData.testConfig.stages.stage2;
});

const appDebug = computed(() => props.appDebug);

function handleStage1Complete(results: CompleteResults) {
    stage1Results.value = results;
    if (results.isUnresolvable) {
        currentStage.value = 0;
    } else {
        currentStage.value = 2;
    }
}

function handleStage2Complete(results: any) {
    stage2Results.value = results;
    currentStage.value = 0; // 0 for summary
}

console.log(props.testData);
</script>

<template>
    <div class="p-6">
        <h1 class="mb-6 text-center text-2xl font-bold">Enneagram Test</h1>

        <Stage1 v-if="currentStage === 1" :config="stage1Config" :debug="appDebug" :questions="stage1Questions" @complete="handleStage1Complete" />

        <Stage2
            v-if="currentStage === 2"
            :config="stage2Config"
            :debug="appDebug"
            :questions="stage2Questions"
            :results-stage1="stage1Results"
            @complete="handleStage2Complete"
        />

        <Summary v-if="currentStage === 0" :debug="appDebug" :stage1-results="stage1Results" :stage2-results="stage2Results" />
    </div>
</template>
