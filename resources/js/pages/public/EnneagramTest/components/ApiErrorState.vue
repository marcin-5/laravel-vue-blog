<script lang="ts" setup>
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    message: string;
    status: number | null;
}>();

const emit = defineEmits<{
    retry: [];
    reset: [];
}>();

const { t } = useI18n();
</script>

<template>
    <section class="mx-auto mb-6 max-w-4xl rounded-lg border border-red-300 bg-card p-6" role="alert" aria-live="assertive">
        <p class="mb-2 font-semibold text-foreground">{{ t('api_error_title') }}</p>
        <p class="mb-4 text-sm text-muted-foreground">{{ props.message }}</p>
        <p v-if="props.status" class="mb-4 text-xs text-muted-foreground">HTTP {{ props.status }}</p>
        <div class="flex flex-wrap gap-3">
            <Button variant="secondary" @click="emit('retry')">{{ t('retry') }}</Button>
            <Button variant="outline" @click="emit('reset')">{{ t('restart') }}</Button>
        </div>
    </section>
</template>