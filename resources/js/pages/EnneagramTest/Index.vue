<template>
    <div class="p-6">
        <h1 class="mb-6 text-center text-2xl font-bold">Enneagram Test</h1>

        <Stage1 v-if="currentStage === 1" :config="stage1Config" :debug="appDebug" :questions="stage1Questions" @complete="handleStage1Complete" />

        <Stage2 v-if="currentStage === 2" :config="stage2Config" :debug="appDebug" :questions="stage2Questions" :results-stage1="stage1Results" />
    </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue';
import Stage1 from './Stage1.vue';
import Stage2 from './Stage2.vue';

const props = defineProps<{
    testData: any;
    appDebug?: boolean;
}>();

const currentStage = ref(1);
const stage1Results = ref<any>(null);

const stage1Questions = computed(() => {
    return props.testData.questions.filter((q: { stage: number }) => q.stage === 1);
});

const stage1Config = computed(() => {
    return props.testData.testConfig.stages.stage1;
});

const stage2Questions = computed(() => {
    return props.testData.questions.filter((q: { stage: number }) => q.stage === 2);
});

const stage2Config = computed(() => {
    return props.testData.testConfig.stages.stage2;
});

const appDebug = computed(() => props.appDebug);

function handleStage1Complete(results: any) {
    stage1Results.value = results;
    currentStage.value = 2;
}
</script>
