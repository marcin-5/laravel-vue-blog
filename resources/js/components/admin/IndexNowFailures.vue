<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { useHttp } from '@inertiajs/vue3';
import { AlertTriangle, Check, ChevronDown, Copy, LoaderCircle, RotateCcw } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { shallowRef, watch } from 'vue';

interface FailedIndexNowJob {
    uuid: string;
    failed_at: string;
    exception: string;
}

interface FailureResponse {
    failures: FailedIndexNowJob[];
}

const props = defineProps<{
    failureCount: number;
}>();

const { t } = useI18n();
const http = useHttp();
const displayedFailureCount = shallowRef(props.failureCount);
const failures = shallowRef<FailedIndexNowJob[]>([]);
const isExpanded = shallowRef(false);
const hasLoaded = shallowRef(false);
const isLoading = shallowRef(false);
const loadFailed = shallowRef(false);
const retryingUuid = shallowRef<string | null>(null);
const retryFailedUuid = shallowRef<string | null>(null);
const copiedUuid = shallowRef<string | null>(null);
const copyFailedUuid = shallowRef<string | null>(null);

watch(
    () => props.failureCount,
    (failureCount) => {
        displayedFailureCount.value = failureCount;
        failures.value = [];
        hasLoaded.value = false;
        isExpanded.value = false;
    },
);

async function loadFailures(): Promise<void> {
    isLoading.value = true;
    loadFailed.value = false;

    try {
        const response = (await http.get(route('admin.logs.indexnow-failures'))) as unknown as FailureResponse;
        failures.value = response.failures;
        hasLoaded.value = true;
    } catch {
        loadFailed.value = true;
    } finally {
        isLoading.value = false;
    }
}

async function toggleDetails(): Promise<void> {
    isExpanded.value = !isExpanded.value;

    if (isExpanded.value && !hasLoaded.value) {
        await loadFailures();
    }
}

async function retryFailure(failure: FailedIndexNowJob): Promise<void> {
    if (retryingUuid.value) {
        return;
    }

    retryingUuid.value = failure.uuid;
    retryFailedUuid.value = null;

    try {
        await http.post(route('admin.logs.indexnow-failures.retry', { uuid: failure.uuid }));
        failures.value = failures.value.filter((failedJob) => failedJob.uuid !== failure.uuid);
        displayedFailureCount.value = Math.max(0, displayedFailureCount.value - 1);
    } catch {
        retryFailedUuid.value = failure.uuid;
    } finally {
        retryingUuid.value = null;
    }
}

async function copyFailure(failure: FailedIndexNowJob): Promise<void> {
    copiedUuid.value = null;
    copyFailedUuid.value = null;

    try {
        await navigator.clipboard.writeText(failure.exception);
        copiedUuid.value = failure.uuid;
    } catch {
        copyFailedUuid.value = failure.uuid;
    }
}
</script>

<template>
    <section
        v-if="displayedFailureCount > 0"
        aria-labelledby="indexnow-failures-heading"
        class="rounded-lg border border-red-300 bg-red-50/70 p-4 text-red-950 dark:border-red-900 dark:bg-red-950/25 dark:text-red-100"
        data-testid="indexnow-failure-signal"
    >
        <Collapsible :open="isExpanded">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <AlertTriangle aria-hidden="true" class="h-4 w-4 shrink-0" />
                    <h2 id="indexnow-failures-heading" class="text-sm font-semibold" data-testid="indexnow-failure-count">
                        {{ t('admin.logs.indexnow_failure_count', { count: displayedFailureCount }) }}
                    </h2>
                </div>
                <CollapsibleTrigger
                    class="inline-flex items-center gap-2 rounded-md border border-current/20 px-3 py-1.5 text-sm font-medium transition-colors hover:bg-red-100/70 disabled:cursor-wait disabled:opacity-60 dark:hover:bg-red-900/40"
                    data-testid="indexnow-failures-toggle"
                    :disabled="isLoading"
                    type="button"
                    @click="toggleDetails"
                >
                    {{ isExpanded ? t('admin.logs.indexnow_hide_failures') : t('admin.logs.indexnow_more') }}
                    <ChevronDown :class="isExpanded ? 'rotate-180' : ''" aria-hidden="true" class="h-4 w-4 transition-transform" />
                </CollapsibleTrigger>
            </div>

            <CollapsibleContent>
                <div class="mt-4 space-y-3 border-t border-red-300/70 pt-4 dark:border-red-900/70">
                    <p class="text-xs text-muted-foreground">{{ t('admin.logs.indexnow_latest_failures') }}</p>
                    <p v-if="isLoading" class="flex items-center gap-2 text-sm" role="status">
                        <LoaderCircle aria-hidden="true" class="h-4 w-4 animate-spin" />
                        {{ t('admin.logs.indexnow_loading') }}
                    </p>
                    <p v-else-if="loadFailed" class="text-sm text-red-700 dark:text-red-300" role="alert">
                        {{ t('admin.logs.indexnow_load_failed') }}
                    </p>
                    <p v-else-if="failures.length === 0" class="text-sm text-muted-foreground">
                        {{ t('admin.logs.indexnow_no_failures') }}
                    </p>
                    <div v-else class="space-y-3">
                        <article
                            v-for="failure in failures"
                            :key="failure.uuid"
                            class="min-w-0 rounded-md border border-red-200 bg-background p-3 dark:border-red-900"
                        >
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <time class="text-xs text-muted-foreground" :datetime="failure.failed_at">
                                    {{ new Date(failure.failed_at).toLocaleString() }}
                                </time>
                                <div class="flex flex-wrap items-center gap-2">
                                    <Button
                                        class="h-7 px-2 text-xs"
                                        :data-testid="`indexnow-copy-${failure.uuid}`"
                                        size="sm"
                                        type="button"
                                        variant="outline"
                                        @click="copyFailure(failure)"
                                    >
                                        <Check v-if="copiedUuid === failure.uuid" aria-hidden="true" class="mr-1 h-3.5 w-3.5" />
                                        <Copy v-else aria-hidden="true" class="mr-1 h-3.5 w-3.5" />
                                        {{ copiedUuid === failure.uuid ? t('admin.logs.indexnow_copied') : t('admin.logs.indexnow_copy') }}
                                    </Button>
                                    <Button
                                        class="h-7 px-2 text-xs"
                                        :data-testid="`indexnow-retry-${failure.uuid}`"
                                        :disabled="retryingUuid !== null"
                                        size="sm"
                                        type="button"
                                        variant="outline"
                                        @click="retryFailure(failure)"
                                    >
                                        <LoaderCircle v-if="retryingUuid === failure.uuid" aria-hidden="true" class="mr-1 h-3.5 w-3.5 animate-spin" />
                                        <RotateCcw v-else aria-hidden="true" class="mr-1 h-3.5 w-3.5" />
                                        {{ retryingUuid === failure.uuid ? t('admin.logs.indexnow_retrying') : t('admin.logs.indexnow_retry') }}
                                    </Button>
                                </div>
                            </div>
                            <pre
                                :data-testid="`indexnow-exception-${failure.uuid}`"
                                class="max-h-64 overflow-auto rounded-md bg-muted/60 p-3 font-mono text-xs leading-relaxed break-words whitespace-pre-wrap"
                                >{{ failure.exception }}</pre>
                            <p v-if="copiedUuid === failure.uuid" class="mt-2 text-xs text-green-700 dark:text-green-400" role="status">
                                {{ t('admin.logs.indexnow_copied') }}
                            </p>
                            <p v-if="copyFailedUuid === failure.uuid" class="mt-2 text-xs text-red-700 dark:text-red-300" role="alert">
                                {{ t('admin.logs.indexnow_copy_failed') }}
                            </p>
                            <p v-if="retryFailedUuid === failure.uuid" class="mt-2 text-xs text-red-700 dark:text-red-300" role="alert">
                                {{ t('admin.logs.indexnow_retry_failed') }}
                            </p>
                        </article>
                    </div>
                </div>
            </CollapsibleContent>
        </Collapsible>
    </section>
</template>
