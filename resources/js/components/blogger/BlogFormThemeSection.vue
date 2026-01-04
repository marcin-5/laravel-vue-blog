<script lang="ts" setup>
import BlogFormColorField from '@/components/blogger/BlogFormColorField.vue';
import type { ThemeColors } from '@/types/blog.types';

interface Props {
    title: string;
    idPrefix: string;
    colors: ThemeColors;
    errors?: Record<string, any>;
    translations: {
        background: string;
        backgroundTooltip: string;
        foreground: string;
        foregroundTooltip: string;
        primary: string;
        primaryTooltip: string;
        primaryForeground: string;
        primaryForegroundTooltip: string;
        secondary: string;
        secondaryTooltip: string;
        secondaryForeground: string;
        secondaryForegroundTooltip: string;
    };
}

const props = defineProps<Props>();

// Define the update event (following v-model convention for the colors object)
const emit = defineEmits<{
    (e: 'update:colors', value: ThemeColors): void;
}>();

// Helper function to update a specific key in an object
function updateColor(key: string, value: string) {
    emit('update:colors', {
        ...props.colors,
        [key]: value,
    });
}
</script>

<template>
    <div>
        <h4 class="mb-2 text-sm font-medium opacity-80">{{ props.title }}</h4>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <BlogFormColorField
                :id="`${props.idPrefix}-background`"
                :error="props.errors?.['--background']"
                :label="props.translations.background"
                :model-value="props.colors['--background']"
                :tooltip="props.translations.backgroundTooltip"
                placeholder="#ffffff"
                @update:model-value="updateColor('--background', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-foreground`"
                :error="props.errors?.['--foreground']"
                :label="props.translations.foreground"
                :model-value="props.colors['--foreground']"
                :tooltip="props.translations.foregroundTooltip"
                placeholder="#0a0a0a"
                @update:model-value="updateColor('--foreground', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-primary`"
                :error="props.errors?.['--primary']"
                :label="props.translations.primary"
                :model-value="props.colors['--primary']"
                :tooltip="props.translations.primaryTooltip"
                placeholder="#111111"
                @update:model-value="updateColor('--primary', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-primary-fg`"
                :error="props.errors?.['--primary-foreground']"
                :label="props.translations.primaryForeground"
                :model-value="props.colors['--primary-foreground']"
                :tooltip="props.translations.primaryForegroundTooltip"
                placeholder="#fafafa"
                @update:model-value="updateColor('--primary-foreground', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-secondary`"
                :error="props.errors?.['--secondary']"
                :label="props.translations.secondary"
                :model-value="props.colors['--secondary']"
                :tooltip="props.translations.secondaryTooltip"
                placeholder="#ececec"
                @update:model-value="updateColor('--secondary', $event)"
            />
            <BlogFormColorField
                :id="`${props.idPrefix}-secondary-fg`"
                :error="props.errors?.['--secondary-foreground']"
                :label="props.translations.secondaryForeground"
                :model-value="props.colors['--secondary-foreground']"
                :tooltip="props.translations.secondaryForegroundTooltip"
                placeholder="#111111"
                @update:model-value="updateColor('--secondary-foreground', $event)"
            />
        </div>
    </div>
</template>
