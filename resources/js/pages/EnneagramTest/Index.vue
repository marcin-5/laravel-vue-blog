<script lang="ts" setup>
import { computed, ref } from 'vue';
import Stage1 from './Stage1.vue';
import Stage2 from './Stage2.vue';
import Summary from './components/Summary.vue';
import { isStage1Question, isStage2Question, type QuestionIdHolder } from './composables/shared/questionIds';
import type { CompleteStage1Results, Stage2Results, TestData } from './composables/shared/types';

const props = defineProps<{
    testData: TestData;
    appDebug?: boolean;
}>();

type CurrentView = 'stage1' | 'stage2' | 'summary';

const currentView = ref<CurrentView>('stage1');
const stage1Results = ref<CompleteStage1Results | null>(null);
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

function handleStage1Complete(results: CompleteStage1Results) {
    stage1Results.value = results;
    if (results.isUnresolvable) {
        currentView.value = 'summary';
    } else {
        currentView.value = 'stage2';
    }
}

function handleStage2Complete(results: Stage2Results) {
    stage2Results.value = results;
    currentView.value = 'summary';
}
</script>

<template>
    <div class="p-6">
        <h1 class="mb-6 text-center text-2xl font-bold">Enneagram Test</h1>

        <Stage1
            v-if="currentView === 'stage1'"
            :config="stage1Config"
            :debug="appDebug"
            :questions="stage1Questions"
            @complete="handleStage1Complete"
        />

        <Stage2
            v-if="currentView === 'stage2' && stage1Results"
            :config="stage2Config"
            :debug="appDebug"
            :questions="stage2Questions"
            :results-stage1="stage1Results"
            @complete="handleStage2Complete"
        />

        <Summary v-if="currentView === 'summary'" :debug="appDebug" :stage1-results="stage1Results" :stage2-results="stage2Results" />
    </div>
</template>
