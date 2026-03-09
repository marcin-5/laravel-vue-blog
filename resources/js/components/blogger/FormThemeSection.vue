<script lang="ts" setup>
import FormColorField from '@/components/blogger/FormColorField.vue';
import FormSelectField from '@/components/blogger/FormSelectField.vue';
import { FONT_OPTIONS, type ThemeTranslations, useThemeSection } from '@/components/blogger/useThemeSection';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Slider } from '@/components/ui/slider';
import { TooltipButton } from '@/components/ui/tooltip';
import { useCssVariables } from '@/composables/useCssVariables';
import { useToast } from '@/composables/useToast';
import type { ThemeColors } from '@/types/blog.types';
import { Download, Settings2, Upload } from 'lucide-vue-next';
import { useTemplateRef } from 'vue';

interface Props {
    title: string;
    idPrefix: string;
    colors?: ThemeColors;
    errors?: Record<string, any>;
    translations: ThemeTranslations;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:colors', value: ThemeColors): void;
}>();

const { toast } = useToast();
const fileInput = useTemplateRef<HTMLInputElement>('fileInput');

const { variables } = useCssVariables([
    '--background',
    '--foreground',
    '--primary',
    '--primary-foreground',
    '--secondary',
    '--secondary-foreground',
    '--muted-foreground',
    '--border',
    '--link',
    '--link-hover',
    '--breadcrumb-link',
    '--breadcrumb-link-active',
    '--card',
]);

const { updateValue, colorFields, fontFields } = useThemeSection(props, emit);

function exportTheme() {
    const data = JSON.stringify(props.colors ?? {}, null, 2);
    const blob = new Blob([data], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    const variant = props.idPrefix.includes('light') ? 'light' : 'dark';
    link.download = `blog-theme-${variant}-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    URL.revokeObjectURL(url);
}

function triggerImport() {
    fileInput.value?.click();
}

async function handleImport(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) {
        return;
    }

    try {
        const text = await file.text();
        const theme = JSON.parse(text);

        // Basic validation
        if (typeof theme !== 'object' || theme === null) {
            toast({
                title: props.translations.importError || 'Import failed',
                variant: 'destructive',
            });
            return;
        }

        let colorsToImport = theme;

        // Compatibility check: if the user tries to import a full theme (with light/dark keys)
        // into a specific section, we should pick the relevant part or the whole object if it's flat.
        if (theme.light || theme.dark) {
            const variant = props.idPrefix.includes('light') ? 'light' : 'dark';
            colorsToImport = theme[variant] || (variant === 'light' ? theme.dark : theme.light) || theme;
        }

        emit('update:colors', {
            ...(props.colors ?? {}),
            ...colorsToImport,
        });
        toast({
            title: props.translations.importSuccess || 'Import successful',
            variant: 'default',
        });
    } catch {
        toast({
            title: props.translations.importError || 'Import failed',
            variant: 'destructive',
        });
    } finally {
        target.value = '';
    }
}
</script>

<template>
    <div>
        <div class="mb-2 flex items-center justify-between">
            <h4 class="text-sm font-medium opacity-80">{{ props.title }}</h4>
            <div class="flex gap-1">
                <input ref="fileInput" accept=".json" class="hidden" type="file" @change="handleImport" />
                <TooltipButton
                    :tooltip-content="props.translations.importTooltip || ''"
                    size="sm"
                    type="button"
                    variant="ghost"
                    @click="triggerImport"
                >
                    <Upload class="h-3.5 w-3.5" />
                </TooltipButton>
                <TooltipButton :tooltip-content="props.translations.exportTooltip || ''" size="sm" type="button" variant="ghost" @click="exportTheme">
                    <Download class="h-3.5 w-3.5" />
                </TooltipButton>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 border-b border-border pb-6 xl:grid-cols-2">
            <template v-for="field in fontFields" :key="`${props.idPrefix}-${field.idSuffix}`">
                <div class="flex w-full items-end gap-2">
                    <FormSelectField
                        :id="`${props.idPrefix}-${field.idSuffix}`"
                        :label="props.translations[field.labelKey] || field.defaultLabel"
                        :model-value="props.colors?.[field.key] || 'inherit'"
                        :options="FONT_OPTIONS"
                        class="grow"
                        @update:model-value="updateValue(field.key, $event.toString())"
                    />
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button class="h-8 w-8 shrink-0" size="icon" variant="ghost">
                                <Settings2 class="h-4 w-4" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-64">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium">{{ props.translations.fontScaleCorrection || 'Size correction' }}</span>
                                    <span class="font-mono text-xs">{{ field.scaleValue.value[0] }}%</span>
                                </div>
                                <Slider
                                    :max="field.scaleMax"
                                    :min="field.scaleMin"
                                    :model-value="field.scaleValue.value"
                                    :step="1"
                                    @update:model-value="field.scaleValue.value = $event as number[]"
                                />
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>
                <FormSelectField
                    v-if="field.additionalField"
                    :id="`${props.idPrefix}-${field.additionalField.idSuffix}`"
                    :label="props.translations[field.additionalField.labelKey] || field.additionalField.defaultLabel"
                    :model-value="props.colors?.[field.additionalField.key] || field.additionalField.defaultValue"
                    :options="field.additionalField.options"
                    class="w-full"
                    @update:model-value="updateValue(field.additionalField!.key, $event.toString())"
                />
            </template>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <FormColorField
                v-for="field in colorFields"
                :id="`${props.idPrefix}-${field.idSuffix}`"
                :key="field.key"
                :error="props.errors?.[field.key]"
                :label="props.translations[field.labelKey]"
                :model-value="props.colors?.[field.key]"
                :placeholder="variables[field.key]"
                :tooltip="props.translations[field.tooltipKey]"
                @update:model-value="updateValue(field.key, $event)"
            />
        </div>
    </div>
</template>
