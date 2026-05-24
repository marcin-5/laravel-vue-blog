<script lang="ts" setup>
import FormThemeSection from '@/components/blogger/FormThemeSection.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { type ThemeTranslations } from '@/composables/useThemeSection';
import type { BlogTheme } from '@/types/blog.types';
import { Info } from 'lucide-vue-next';
import { computed } from 'vue';

interface EntityThemeTranslations extends ThemeTranslations {
    title: string;
    description: string;
    light: string;
    dark: string;
    advancedHint: string;
}

interface Props {
    modelValue: BlogTheme | null | undefined;
    errors: Record<string, string>;
    idPrefix: string;
    translations: EntityThemeTranslations;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:modelValue', value: BlogTheme): void;
}>();

const themeLight = computed({
    get: () => props.modelValue?.light ?? {},
    set: (value) => {
        emit('update:modelValue', {
            ...(props.modelValue ?? {}),
            light: value,
        });
    },
});

const themeDark = computed({
    get: () => props.modelValue?.dark ?? {},
    set: (value) => {
        emit('update:modelValue', {
            ...(props.modelValue ?? {}),
            dark: value,
        });
    },
});

function filterErrorsByPrefix(errors: Record<string, string>, prefix: string): Record<string, string> {
    const filtered: Record<string, string> = {};
    for (const key of Object.keys(errors)) {
        if (key.startsWith(prefix)) {
            filtered[key.replace(prefix, '')] = errors[key];
        }
    }
    return filtered;
}

const themeLightErrors = computed(() => filterErrorsByPrefix(props.errors, 'theme.light.'));
const themeDarkErrors = computed(() => filterErrorsByPrefix(props.errors, 'theme.dark.'));
</script>

<template>
    <div class="mt-4 rounded-md border border-border p-4">
        <div class="mb-3 flex items-center gap-2">
            <h3 class="text-lg font-semibold">{{ translations.title }}</h3>
            <TooltipProvider>
                <Tooltip :delay-duration="0">
                    <TooltipTrigger as-child>
                        <button class="flex items-center justify-center text-muted-foreground hover:text-foreground" type="button">
                            <Info class="h-4 w-4" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent class="max-w-xs" side="top">
                        <p class="text-sm">{{ translations.description }}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <FormThemeSection
                v-model:colors="themeLight"
                :errors="themeLightErrors"
                :id-prefix="`${idPrefix}-theme-light`"
                :title="translations.light"
                :translations="translations"
            />
            <FormThemeSection
                v-model:colors="themeDark"
                :errors="themeDarkErrors"
                :id-prefix="`${idPrefix}-theme-dark`"
                :title="translations.dark"
                :translations="translations"
            />
        </div>
        <div class="mt-2 text-xs text-muted-foreground">
            {{ translations.advancedHint }}
        </div>
    </div>
</template>
