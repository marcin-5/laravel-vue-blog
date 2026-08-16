<script lang="ts" setup>
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    autoConfirmSingle: boolean;
    processing: boolean;
}>();

const emit = defineEmits<{
    start: [extended: boolean];
    'update:autoConfirmSingle': [value: boolean];
}>();

const { t, tm } = useI18n();

function updateAutoConfirm(event: Event): void {
    emit('update:autoConfirmSingle', (event.target as HTMLInputElement).checked);
}
</script>

<template>
    <section class="mx-auto max-w-4xl rounded-lg bg-card p-6 shadow-md md:p-8" aria-labelledby="enneagram-start-title">
        <h2 id="enneagram-start-title" class="mb-4 font-quicksand text-xl font-semibold text-foreground">
            {{ t('welcome') }}
        </h2>
        <p class="mb-4 text-primary">{{ t('description') }}</p>

        <div class="mb-6 space-y-2 text-primary">
            <p><strong>{{ t('rules_title') }}</strong></p>
            <ul class="list-inside list-disc">
                <li v-for="(rule, index) in tm('rules')" :key="index">{{ rule }}</li>
            </ul>
        </div>

        <div class="mb-6 rounded-md border p-4">
            <label class="flex items-start gap-3">
                <input
                    :checked="props.autoConfirmSingle"
                    class="mt-1 size-4"
                    type="checkbox"
                    @change="updateAutoConfirm"
                />
                <span class="flex flex-col">
                    <span class="font-medium">{{ t('auto_confirm_single_label') }}</span>
                    <span class="text-sm text-muted-foreground">{{ t('auto_confirm_single_help') }}</span>
                </span>
            </label>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row">
            <Button
                class="flex-1 py-6 font-medium"
                :disabled="props.processing"
                size="lg"
                @click="emit('start', false)"
            >
                {{ props.processing ? t('loading') : t('start_standard') }}
            </Button>
            <Button
                class="flex-1 py-6 font-medium"
                :disabled="props.processing"
                size="lg"
                variant="outline"
                @click="emit('start', true)"
            >
                {{ t('start_extended') }}
            </Button>
        </div>
        <p class="mt-4 text-center text-sm text-muted-foreground">{{ t('extended_info') }}</p>
    </section>
</template>