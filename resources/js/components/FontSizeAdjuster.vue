<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Slider } from '@/components/ui/slider';
import { useFontSize } from '@/composables/useFontSize';
import { TypeIcon } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { fontSize, updateFontSize } = useFontSize();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button class="h-9 w-9" size="icon" variant="toggle">
                <TypeIcon class="h-4 w-4" />
                <span class="sr-only">{{ t('common.nav.font_size.adjust') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56 p-4">
            <DropdownMenuLabel class="px-0 pt-0 text-center"> {{ t('common.nav.font_size.label') }}: {{ fontSize[0] }}% </DropdownMenuLabel>
            <DropdownMenuSeparator class="-mx-4 my-4" />
            <div class="py-2">
                <Slider
                    :max="120"
                    :min="80"
                    :model-value="fontSize"
                    :step="5"
                    class="w-full"
                    @update:model-value="updateFontSize($event as number[])"
                />
            </div>
            <div class="mt-4 flex justify-between text-xs text-muted-foreground">
                <span>80%</span>
                <span>100%</span>
                <span>120%</span>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
