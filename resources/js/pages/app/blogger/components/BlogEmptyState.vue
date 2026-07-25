<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { useI18n } from 'vue-i18n';

defineProps<{
    canCreate: boolean;
}>();

const emit = defineEmits<{
    create: [];
}>();

const { t } = useI18n();
</script>

<template>
    <div class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
        {{ t('blogger.empty') }}
        <span :title="!canCreate ? t('blogger.limit_reached_hint') : ''">
            <Button :disabled="!canCreate" :variant="!canCreate ? 'muted' : 'link'" class="ml-2" type="button" @click="emit('create')">
                {{ t('blogger.empty_cta') }}
            </Button>
        </span>
    </div>
</template>
