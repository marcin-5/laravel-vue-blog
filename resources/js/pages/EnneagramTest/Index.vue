<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold">Enneagram Test</h1>
        <Stage1 :config="stage1Config" :debug="appDebug" :questions="stage1Questions" />
    </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import Stage1 from './Stage1.vue';

const props = defineProps<{
    testData: any;
    appDebug?: boolean;
}>();

const stage1Questions = computed(() => {
    return props.testData.questions.filter((q: { stage: number }) => q.stage === 1);
});

const stage1Config = computed(() => {
    return props.testData.testConfig.stages.stage1;
});

const appDebug = computed(() => props.appDebug);
</script>
