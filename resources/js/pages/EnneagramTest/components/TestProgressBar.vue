<script lang="ts" setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { LeadIndicator } from '../composables/shared/types';

interface Props {
    current: number;
    min?: number;
    max: number;
    total: number;
    stage?: number;
    part?: number;
    leads?: LeadIndicator[];
}

const props = defineProps<Props>();
const { t } = useI18n();

const showExplanation = ref(false);

const descriptionKey = computed(() => (props.stage === 2 ? 'lead_progress_desc_stage2' : 'lead_progress_desc'));

const hasMinMarker = computed(() => props.min !== undefined && props.min !== props.max);
const hasLeads = computed(() => props.leads !== undefined && props.leads.length > 0);
const isExtra = computed(() => props.current > props.max);

function toPercent(value: number): number {
    if (props.total === 0) {
        return 0;
    }

    return Math.min((value / props.total) * 100, 100);
}

function leadPercent(lead: LeadIndicator): number {
    if (lead.target <= 0) {
        return 0;
    }

    return Math.min(100, (Math.max(0, lead.current) / lead.target) * 100);
}

const minPercent = computed(() => (props.min === undefined ? 0 : toPercent(props.min)));
const maxPercent = computed(() => toPercent(props.max));
const currentPercent = computed(() => toPercent(Math.min(props.current, props.max)));

const extraPercent = computed(() => {
    if (!isExtra.value) {
        return 0;
    }

    const extraCount = Math.min(props.current, props.total) - props.max;

    return Math.min(toPercent(extraCount), 100 - maxPercent.value);
});
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
                v-if="hasMinMarker"
                :style="{ left: `${minPercent}%` }"
                :title="t('min_questions_point')"
                class="absolute top-0 z-10 h-full w-0.5 bg-green-500/50"
            >
                <div class="absolute -bottom-1 -left-1 h-2 w-2 rounded-full bg-green-500/50"></div>
            </div>
        </div>

        <div class="relative mt-1 flex h-4 justify-between px-1 text-xs text-muted-foreground">
            <span>0</span>
            <span
                v-if="hasMinMarker"
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
        <div v-if="hasLeads" class="mt-4 space-y-3 rounded-lg border border-border/50 bg-secondary/10 p-2 md:p-3">
            <div v-for="lead in leads" :key="lead.label" class="space-y-1.5">
                <div class="flex justify-between text-xs font-bold tracking-wider text-muted-foreground uppercase">
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
                        :style="{ width: `${leadPercent(lead)}%` }"
                        class="h-full transition-all duration-700 ease-out"
                    ></div>

                    <!-- Target marker -->
                    <div class="absolute top-0 right-0 h-full w-px bg-foreground/20"></div>
                </div>
            </div>
            <div class="mt-2 flex flex-col items-center">
                <button
                    class="group flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                    type="button"
                    @click="showExplanation = !showExplanation"
                >
                    {{ showExplanation ? t('hide_explanation') : t('show_explanation') }}

                    <svg
                        :class="['h-3 w-3 transition-transform duration-200', showExplanation ? 'rotate-180' : '']"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path d="m19.5 8.25-7.5 7.5-7.5-7.5" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
                    </svg>
                </button>

                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 transform opacity-0"
                    enter-to-class="scale-100 transform opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 transform opacity-100"
                    leave-to-class="scale-95 transform opacity-0"
                >
                    <p v-if="showExplanation" class="mt-2 text-center text-sm leading-relaxed text-muted italic">
                        {{ t(descriptionKey) }}
                    </p>
                </transition>
            </div>
        </div>
    </div>
</template>

<style scoped>
.test-progress-bar {
    position: relative;
}
</style>
