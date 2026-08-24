<script lang="ts" setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { EnneagramDebugState } from '../types';

const props = defineProps<{
    debug: EnneagramDebugState;
}>();

const { t } = useI18n();

const stageOneParts = computed(() => Object.entries(props.debug.stage1));
const stageTwoParts = computed(() => Object.entries(props.debug.stage2.perPart).sort(([first], [second]) => Number(first) - Number(second)));
</script>

<template>
    <section
        aria-labelledby="enneagram-debug-title"
        class="mx-auto max-w-4xl rounded-lg border border-dashed border-primary/50 bg-muted/30 p-4 text-card-foreground"
        aria-live="polite"
    >
        <h2 id="enneagram-debug-title" class="mb-4 text-lg font-bold text-foreground">{{ t('debug_data') }}</h2>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <h3 class="mb-2 text-sm font-bold text-foreground uppercase">{{ t('stage1_instincts') }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div v-for="[part, scores] in stageOneParts" :key="part" class="rounded border border-muted bg-card p-2">
                        <h4 class="mb-1 border-b border-muted pb-1 text-xs font-bold">{{ t('part') }} {{ part.replace('part', '') }}</h4>
                        <dl class="grid grid-cols-3 gap-2 text-xs">
                            <div v-for="[category, score] in Object.entries(scores)" :key="category" class="flex flex-col">
                                <dt class="text-muted-foreground uppercase">{{ category }}</dt>
                                <dd class="font-bold text-foreground">{{ score }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="mb-2 text-sm font-bold text-foreground uppercase">{{ t('stage2_types') }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div v-for="[part, scores] in stageTwoParts" :key="part" class="rounded border border-muted bg-card p-2">
                        <h4 class="mb-1 border-b border-muted pb-1 text-xs font-bold">{{ t('part') }} {{ part }}</h4>
                        <dl class="grid grid-cols-3 gap-2 text-xs">
                            <div v-for="[type, score] in Object.entries(scores)" :key="type" class="flex flex-col">
                                <dt class="text-muted-foreground">{{ t('type_label', { type }) }}</dt>
                                <dd class="font-bold text-foreground">{{ score }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="mt-2 rounded border border-muted bg-card p-2">
                    <h4 class="mb-1 border-b border-muted pb-1 text-xs font-bold">{{ t('total_sum') }}</h4>
                    <dl class="grid grid-cols-3 gap-2 text-xs sm:grid-cols-5">
                        <div v-for="[type, score] in Object.entries(props.debug.stage2.total)" :key="type" class="flex flex-col">
                            <dt class="text-muted-foreground">{{ t('type_label', { type }) }}</dt>
                            <dd class="font-bold text-foreground">{{ score }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </section>
</template>
