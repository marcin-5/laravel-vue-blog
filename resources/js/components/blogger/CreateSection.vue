<script lang="ts" setup>
import { TooltipButton } from '@/components/ui/tooltip';
import { Plus, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    title: string;
    canCreate: boolean;
    tooltipCreate: string;
    tooltipLimit: string;
    tooltipClose?: string;
}

defineProps<Props>();
const showCreate = defineModel<boolean>('showCreate', { required: true });
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
                @click="showCreate = !showCreate"
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
