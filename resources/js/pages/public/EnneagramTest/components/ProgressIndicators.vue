<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { EnneagramTestState, LeadProgress, TestMapPartStatus } from '../types';

const props = defineProps<{
    state: EnneagramTestState;
}>();

const { t } = useI18n();

const progressPercentage = computed(() => {
    if (props.state.progress.target <= 0) {
        return 0;
    }

    return Math.min(100, (props.state.progress.answered / props.state.progress.target) * 100);
});

const leadDescription = computed(() => (props.state.stage === 2 ? t('lead_progress_desc_stage2') : t('lead_progress_desc')));

function leadPercentage(lead: LeadProgress): number {
    if (lead.target <= 0) {
        return 0;
    }

    return Math.min(100, (lead.value / lead.target) * 100);
}

function statusIcon(status: TestMapPartStatus): string {
    return {
        completed: '✓',
        active: '●',
        pending: '○',
        skipped: '—',
    }[status];
}

function statusLabel(status: TestMapPartStatus): string {
    return t(`part_status.${status}`);
}

function statusClasses(status: TestMapPartStatus): string {
    return {
        completed: 'border-primary/40 bg-primary/10 text-primary',
        active: 'border-primary bg-secondary text-secondary-foreground ring-2 ring-primary/30',
        pending: 'border-muted bg-muted/20 text-muted-foreground',
        skipped: 'border-muted bg-muted/10 text-muted-foreground line-through',
    }[status];
}
</script>

<template>
    <section class="mb-6 space-y-4" aria-labelledby="enneagram-progress-title">
        <h3 id="enneagram-progress-title" class="sr-only">{{ t('progress') }}</h3>

        <div class="rounded-lg border border-muted bg-card p-4 text-card-foreground shadow-sm">
            <div class="mb-2 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase">
                        {{ t('stage') }} {{ props.state.stage }} · {{ t('part') }} {{ props.state.part }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-foreground">
                        {{ t('answered_questions') }}: {{ props.state.progress.answered }} / {{ props.state.progress.target }}
                    </p>
                </div>
                <span class="text-sm font-semibold text-foreground"> {{ props.state.progress.position }} / {{ props.state.progress.poolSize }} </span>
            </div>

            <div
                class="h-2 overflow-hidden rounded-full bg-secondary/30"
                role="progressbar"
                :aria-valuemax="props.state.progress.target"
                :aria-valuenow="Math.min(props.state.progress.answered, props.state.progress.target)"
                :aria-valuetext="`${props.state.progress.answered}/${props.state.progress.target}`"
            >
                <div
                    class="h-full rounded-full bg-primary transition-[width] duration-300 motion-reduce:transition-none"
                    :style="{ width: `${progressPercentage}%` }"
                />
            </div>

            <div class="mt-2 flex flex-wrap justify-between gap-x-4 gap-y-1 text-xs text-muted-foreground">
                <span>{{ t('pool_position', { position: props.state.progress.position, poolSize: props.state.progress.poolSize }) }}</span>
                <span>{{ t('target') }}: {{ props.state.progress.target }}</span>
            </div>

            <div
                :class="
                    props.state.progress.phase === 'tie_breaker'
                        ? 'border-primary/40 bg-primary/10 text-primary'
                        : 'border-muted bg-muted/20 text-muted-foreground'
                "
                class="mt-3 rounded-md border px-3 py-2 text-sm"
            >
                <span class="font-semibold">
                    {{ props.state.progress.phase === 'tie_breaker' ? t('tie_breaker_active') : t('standard_phase') }}
                </span>
                <span v-if="props.state.progress.tieBreakerStartedAt !== null" class="ml-2 text-xs">
                    {{ t('tie_breaker_started', { answered: props.state.progress.tieBreakerStartedAt }) }}
                </span>
            </div>
        </div>

        <div class="rounded-lg border border-muted bg-card p-4 text-card-foreground shadow-sm">
            <h4 class="mb-3 text-sm font-semibold text-foreground">{{ t('test_map') }}</h4>
            <div class="grid gap-3 sm:grid-cols-2">
                <div v-for="stage in props.state.test_map" :key="stage.stage" class="space-y-2">
                    <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">{{ t('stage') }} {{ stage.stage }}</p>
                    <ol class="flex flex-wrap gap-2">
                        <li v-for="part in stage.parts" :key="part.part">
                            <span
                                :class="statusClasses(part.status)"
                                class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold"
                                :aria-current="part.status === 'active' ? 'step' : undefined"
                                :title="statusLabel(part.status)"
                            >
                                <span aria-hidden="true">{{ statusIcon(part.status) }}</span>
                                {{ t('part') }} {{ part.part }}
                                <span class="sr-only">{{ statusLabel(part.status) }}</span>
                            </span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div
                v-for="lead in [
                    { key: 'firstSecond', label: t('lead_leader_vs_second') },
                    { key: 'secondThird', label: t('lead_second_vs_third') },
                ]"
                :key="lead.key"
                class="rounded-lg border border-muted bg-card p-4 text-card-foreground shadow-sm"
            >
                <div class="mb-2 flex items-start justify-between gap-3 text-sm">
                    <span class="font-semibold text-foreground">{{ lead.label }}</span>
                    <span class="shrink-0 text-muted-foreground">
                        {{ props.state.progress.lead[lead.key].value }} / {{ props.state.progress.lead[lead.key].target }}
                    </span>
                </div>
                <div
                    class="h-2 overflow-hidden rounded-full bg-secondary/30"
                    role="progressbar"
                    :aria-valuemax="props.state.progress.lead[lead.key].target"
                    :aria-valuenow="Math.min(props.state.progress.lead[lead.key].value, props.state.progress.lead[lead.key].target)"
                    :aria-valuetext="`${props.state.progress.lead[lead.key].value}/${props.state.progress.lead[lead.key].target}`"
                >
                    <div
                        class="h-full rounded-full bg-secondary transition-[width] duration-300 motion-reduce:transition-none"
                        :style="{ width: `${leadPercentage(props.state.progress.lead[lead.key])}%` }"
                    />
                </div>
            </div>
        </div>

        <div
            class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-muted bg-muted/20 px-4 py-3 text-sm text-muted-foreground"
        >
            <span>{{ t('skips') }}</span>
            <span class="font-semibold text-foreground">{{ props.state.skip_count }} / {{ props.state.skip_limit }}</span>
        </div>

        <p class="text-xs text-muted-foreground">{{ leadDescription }}</p>
    </section>
</template>
