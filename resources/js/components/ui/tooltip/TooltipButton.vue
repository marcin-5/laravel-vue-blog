<script lang="ts" setup>
import type { ButtonVariants } from '@/components/ui/button';
import { Button } from '@/components/ui/button';
import type { HTMLAttributes } from 'vue';
import Tooltip from './Tooltip.vue';
import TooltipContent from './TooltipContent.vue';
import TooltipTrigger from './TooltipTrigger.vue';

interface Props {
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    class?: HTMLAttributes['class'];
    tooltipContent: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    size: 'default',
    type: 'button',
});

defineEmits<{
    (e: 'click', event: MouseEvent): void;
}>();
</script>

<template>
    <Tooltip>
        <TooltipTrigger as-child>
            <Button :disabled="disabled" :size="size" :type="type" :variant="variant" @click="$emit('click', $event)">
                <slot />
            </Button>
        </TooltipTrigger>
        <TooltipContent>
            <slot name="tooltip">{{ tooltipContent }}</slot>
        </TooltipContent>
    </Tooltip>
</template>
