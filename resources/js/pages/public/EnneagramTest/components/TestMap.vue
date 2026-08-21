<script lang="ts" setup>
import { useI18n } from 'vue-i18n';
import type { TestMapPartStatus, TestMapStage } from '../types';

defineProps<{
    testMap: TestMapStage[];
}>();

const { t } = useI18n();

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
    <section aria-labelledby="enneagram-test-map-title" class="mb-6">
        <div class="rounded-lg border border-muted bg-card p-4 text-card-foreground shadow-sm">
            <h3 id="enneagram-test-map-title" class="mb-3 text-sm font-semibold text-foreground">{{ t('test_map') }}</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <div v-for="stage in testMap" :key="stage.stage" class="space-y-2">
                    <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">{{ t('stage') }} {{ stage.stage }}</p>
                    <ol class="flex flex-wrap gap-2">
                        <li v-for="part in stage.parts" :key="part.part">
                            <span
                                :aria-current="part.status === 'active' ? 'step' : undefined"
                                :class="statusClasses(part.status)"
                                :title="statusLabel(part.status)"
                                class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold"
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
    </section>
</template>
