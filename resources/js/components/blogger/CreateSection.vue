<script generic="TForm" lang="ts" setup>
import { TooltipButton } from '@/components/ui/tooltip';
import { Plus, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    title: string;
    canCreate: boolean;
    showCreate: boolean;
    tooltipCreate: string;
    tooltipLimit: string;
    tooltipClose?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'toggle'): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ title }}</h1>
            <TooltipButton
                :disabled="!canCreate"
                :variant="!canCreate ? 'muted' : showCreate ? 'exit' : 'constructive'"
                size="icon"
                tooltip-content=""
                @click="emit('toggle')"
            >
                <X v-if="showCreate" />
                <Plus v-else />
                <template #tooltip>
                    <template v-if="!canCreate">
                        {{ tooltipLimit }}
                    </template>
                    <template v-else>
                        {{ showCreate ? tooltipClose || t('blogger.actions.close') : tooltipCreate }}
                    </template>
                </template>
            </TooltipButton>
        </div>

        <div v-if="showCreate">
            <slot name="form" />
        </div>
    </div>
</template>
