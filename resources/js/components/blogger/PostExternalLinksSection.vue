<script lang="ts" setup>
import { Button } from '@/components/ui/button/index';
import { Input } from '@/components/ui/input/index';
import { Label } from '@/components/ui/label/index';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover/index';
import { Textarea } from '@/components/ui/textarea/index';
import type { ExternalLinkItem } from '@/types/blog.types';
import { Info, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    items: ExternalLinkItem[];
    idPrefix: string;
    errors?: Record<string, string>;
    translations: {
        label: string;
        addItem: string;
        title: string;
        url: string;
        description: string;
        reason: string;
    };
}

interface Emits {
    (e: 'add-item', item: { title: string; url: string; description: string; reason: string }): void;
    (e: 'remove', index: number): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const newTitle = ref('');
const newUrl = ref('');
const newDescription = ref('');
const newReason = ref('');

const isSubmitDisabled = computed(() => {
    if (!newTitle.value || !newUrl.value) {
        return true;
    }

    if (newDescription.value && !newReason.value) {
        return true;
    }

    return false;
});

const handleAddItem = () => {
    if (!isSubmitDisabled.value) {
        let url = newUrl.value.trim();

        if (url && !/^[a-z][a-z0-9+.-]*:\/\//i.test(url)) {
            url = `https://${url}`;
        }

        emit('add-item', {
            title: newTitle.value,
            url: url,
            description: newDescription.value,
            reason: newReason.value,
        });
        newTitle.value = '';
        newUrl.value = '';
        newDescription.value = '';
        newReason.value = '';
    }
};
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">{{ translations.label }}</h3>
        </div>

        <!-- Add new external link form -->
        <div class="space-y-4 pb-4">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-new-title`">{{ translations.title }}</Label>
                    <Input :id="`${idPrefix}-new-title`" v-model="newTitle" />
                </div>
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-new-url`">{{ translations.url }}</Label>
                    <Input :id="`${idPrefix}-new-url`" v-model="newUrl" />
                </div>
            </div>
            <div class="space-y-2">
                <Label :for="`${idPrefix}-new-reason`">{{ translations.reason }}</Label>
                <Input :id="`${idPrefix}-new-reason`" v-model="newReason" />
            </div>
            <div class="flex flex-col items-end gap-4 lg:flex-row">
                <div class="w-full flex-1 space-y-2">
                    <Label :for="`${idPrefix}-new-desc`">{{ translations.description }}</Label>
                    <Textarea :id="`${idPrefix}-new-desc`" v-model="newDescription" rows="3" />
                    <p v-if="newDescription && !newReason" class="text-xs text-destructive">Powód jest wymagany, gdy podano opis.</p>
                </div>
                <Button :disabled="isSubmitDisabled" class="w-full justify-end lg:w-auto" type="button" variant="outline" @click="handleAddItem">
                    <Plus class="mr-2 h-4 w-4" />
                    {{ translations.addItem }}
                </Button>
            </div>
        </div>

        <!-- List of added links -->
        <div v-if="errors" class="space-y-1">
            <p v-for="(error, key) in errors" :key="key" class="text-xs text-destructive">
                {{ error }}
            </p>
        </div>

        <div v-for="(el, index) in items" :key="index" class="flex items-start justify-between border-t py-2 first:border-t-0 first:pt-0 last:pb-0">
            <div class="flex-1 space-y-1">
                <div class="text-sm font-medium">
                    <a :href="el.url" class="text-primary hover:underline" target="_blank">{{ el.title }}</a>
                </div>
                <div v-if="el.reason" class="flex items-center gap-1.5 text-xs text-muted-foreground/80 italic">
                    {{ el.reason }}
                    <Popover v-if="el.description">
                        <PopoverTrigger as-child>
                            <button
                                class="inline-flex items-center justify-center rounded-full hover:text-foreground focus:outline-none"
                                type="button"
                            >
                                <Info class="h-3.5 w-3.5" />
                                <span class="sr-only">Opis</span>
                            </button>
                        </PopoverTrigger>
                        <PopoverContent class="w-80" side="right">
                            <div class="space-y-2">
                                <h4 class="leading-none font-medium">{{ translations.description }}</h4>
                                <p class="text-sm whitespace-pre-wrap text-muted-foreground">
                                    {{ el.description }}
                                </p>
                            </div>
                        </PopoverContent>
                    </Popover>
                </div>
            </div>
            <div class="ml-4 flex items-center">
                <Button
                    class="h-8 w-8 text-destructive hover:bg-destructive/10"
                    size="icon"
                    type="button"
                    variant="ghost"
                    @click="$emit('remove', index)"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
