<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExternalLinkItem } from '@/types/blog.types';
import { Plus, Trash2 } from 'lucide-vue-next';

interface Props {
    items: ExternalLinkItem[];
    idPrefix: string;
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
    (e: 'add'): void;
    (e: 'remove', index: number): void;
}

defineProps<Props>();
defineEmits<Emits>();
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">{{ translations.label }}</h3>
            <Button size="sm" type="button" variant="outline" @click="$emit('add')">
                <Plus class="mr-2 h-4 w-4" />
                {{ translations.addItem }}
            </Button>
        </div>

        <div v-for="(el, index) in items" :key="index" class="space-y-4 border-t pt-4 first:border-t-0 first:pt-0">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-el-${index}-title`">{{ translations.title }}</Label>
                    <Input :id="`${idPrefix}-el-${index}-title`" v-model="el.title" />
                </div>
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-el-${index}-url`">{{ translations.url }}</Label>
                    <Input :id="`${idPrefix}-el-${index}-url`" v-model="el.url" />
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-el-${index}-desc`">{{ translations.description }}</Label>
                    <Input :id="`${idPrefix}-el-${index}-desc`" v-model="el.description" />
                </div>
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-el-${index}-reason`">{{ translations.reason }}</Label>
                    <div class="flex gap-2">
                        <Input :id="`${idPrefix}-el-${index}-reason`" v-model="el.reason" />
                        <Button size="icon" type="button" variant="destructive" @click="$emit('remove', index)">
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
