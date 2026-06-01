<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Lead {
    label: string;
    current: number;
    target: number;
    color?: string;
}

interface Props {
    current: number;
    min?: number;
    max: number;
    total: number;
    stage?: number;
    part?: number;
    leads?: Lead[];
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
            <div :style="{ width: `${currentPercent}%` }" class="absolute top-0 left-0 h-full bg-primary transition-all duration-500 ease-out"></div>

            <!-- Extra (tie-breaker) progress -->
            <div
                :style="{
                    left: `${maxPercent}%`,
                    width: `${extraPercent}%`,
                }"
                class="absolute top-0 h-full bg-orange-400 transition-all duration-500 ease-out"
            ></div>

            <!-- Max Questions marker -->
            <div :style="{ left: `${maxPercent}%` }" :title="t('tie_breaker_point')" class="absolute top-0 z-10 h-full w-0.5 bg-foreground/50">
                <div class="absolute -top-1 -left-1 h-2 w-2 rounded-full bg-foreground/50"></div>
            </div>

            <!-- Min Questions marker -->
            <div
                v-if="min !== undefined"
                :style="{ left: `${minPercent}%` }"
                :title="t('min_questions_point')"
                class="absolute top-0 z-10 h-full w-0.5 bg-green-500/50"
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

        <!-- Lead indicators -->
        <div v-if="leads && leads.length > 0" class="mt-4 space-y-3 rounded-lg border border-border/50 bg-secondary/10 p-2 md:p-3">
            <div v-for="(lead, index) in leads" :key="index" class="space-y-1.5">
                <div class="flex justify-between text-[10px] font-bold tracking-wider text-muted-foreground uppercase">
                    <span class="flex items-center gap-1.5">
                        <span :class="['h-2 w-2 rounded-full', lead.color || 'bg-primary']"></span>
                        {{ lead.label }}
                    </span>
                    <span class="font-mono">{{ Math.max(0, lead.current) }} / {{ lead.target }}</span>
                </div>
                <div
                    :title="`${lead.label}: ${lead.current}/${lead.target}`"
                    class="relative h-2 w-full overflow-hidden rounded-full bg-secondary/30 shadow-inner"
                >
                    <div
                        :class="[lead.color || 'bg-primary', lead.current >= lead.target ? 'animate-pulse' : '']"
                        :style="{ width: `${Math.min(100, (Math.max(0, lead.current) / lead.target) * 100)}%` }"
                        class="h-full transition-all duration-700 ease-out"
                    ></div>

                    <!-- Target marker -->
                    <div class="absolute top-0 right-0 h-full w-px bg-foreground/20"></div>
                </div>
            </div>
            <p v-if="leads.length > 0" class="mt-1 text-center text-[9px] text-muted/90 italic">
                {{ t('lead_progress_desc') }}
            </p>
        </div>
    </div>
</template>

<style scoped>
.test-progress-bar {
    position: relative;
}
</style>
