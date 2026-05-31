<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    current: number;
    min?: number;
    max: number;
    total: number;
    stage?: number;
    part?: number;
}

const props = defineProps<Props>();
const { t } = useI18n();

const minPercent = computed(() => {
    if (props.total === 0 || props.min === undefined) return 0;
    return Math.min((props.min / props.total) * 100, 100);
});

const maxPercent = computed(() => {
    if (props.total === 0) return 0;
    return Math.min((props.max / props.total) * 100, 100);
});

const currentPercent = computed(() => {
    if (props.total === 0) return 0;
    return Math.min((Math.min(props.current, props.max) / props.total) * 100, 100);
});

const extraPercent = computed(() => {
    if (props.total === 0 || props.current <= props.max) return 0;
    const extraCount = Math.min(props.current, props.total) - props.max;
    return Math.min((extraCount / props.total) * 100, 100 - maxPercent.value);
});

const isExtra = computed(() => props.current > props.max);
</script>

<template>
    <div class="test-progress-bar w-full">
        <div class="mb-2 flex justify-between text-xs font-medium text-muted-foreground">
            <span>{{ t('progress') }}: {{ current }} / {{ total }}</span>
            <span v-if="isExtra" class="animate-pulse font-bold text-orange-500">
                {{ t('tie_breaker_active') }}
            </span>
            <span v-else>
                {{ t('standard_phase') }}
            </span>
        </div>

        <div class="relative h-4 w-full overflow-hidden rounded-full border border-border bg-secondary/30">
            <!-- Standard progress -->
            <div class="absolute top-0 left-0 h-full bg-primary transition-all duration-500 ease-out" :style="{ width: `${currentPercent}%` }"></div>

            <!-- Extra (tie-breaker) progress -->
            <div
                class="absolute top-0 h-full bg-orange-400 transition-all duration-500 ease-out"
                :style="{
                    left: `${maxPercent}%`,
                    width: `${extraPercent}%`,
                }"
            ></div>

            <!-- Max Questions marker -->
            <div class="absolute top-0 z-10 h-full w-0.5 bg-foreground/50" :style="{ left: `${maxPercent}%` }" :title="t('tie_breaker_point')">
                <div class="absolute -top-1 -left-1 h-2 w-2 rounded-full bg-foreground/50"></div>
            </div>

            <!-- Min Questions marker -->
            <div
                v-if="min !== undefined"
                class="absolute top-0 z-10 h-full w-0.5 bg-green-500/50"
                :style="{ left: `${minPercent}%` }"
                :title="t('min_questions_point')"
            >
                <div class="absolute -bottom-1 -left-1 h-2 w-2 rounded-full bg-green-500/50"></div>
            </div>
        </div>

        <div class="relative mt-1 flex h-4 justify-between px-1 text-[10px] text-muted-foreground">
            <span>0</span>
            <span
                v-if="min !== undefined"
                :style="{ left: `${minPercent}%`, transform: 'translateX(-50%)' }"
                class="absolute font-medium whitespace-nowrap text-green-600"
            >
                {{ min }} ({{ t('min') }})
            </span>
            <span :style="{ left: `${maxPercent}%`, transform: 'translateX(-50%)' }" class="absolute whitespace-nowrap">
                {{ max }} ({{ t('target') }})
            </span>
            <span class="ml-auto">{{ total }}</span>
        </div>
    </div>
</template>

<style scoped>
.test-progress-bar {
    position: relative;
}
</style>
