<script lang="ts" setup>
import { Button } from '@/components/ui/button/index';
import { Input } from '@/components/ui/input/index';
import { Label } from '@/components/ui/label/index';
import type { RelatedPostItem } from '@/types/blog.types';
import { Plus, Trash2 } from 'lucide-vue-next';

interface Props {
    items: RelatedPostItem[];
    idPrefix: string;
    translations: {
        label: string;
        addItem: string;
        blogId: string;
        postId: string;
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
            <Button size="sm" type="button" variant="outline" @click.stop="$emit('add')">
                <Plus class="mr-2 h-4 w-4" />
                {{ translations.addItem }}
            </Button>
        </div>

        <div v-for="(rp, index) in items" :key="index" class="space-y-4 border-t pt-4 first:border-t-0 first:pt-0">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-rp-${index}-blog`">{{ translations.blogId }} ID</Label>
                    <Input :id="`${idPrefix}-rp-${index}-blog`" v-model="rp.blog_id" type="number" />
                </div>
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-rp-${index}-post`">{{ translations.postId }} ID</Label>
                    <Input :id="`${idPrefix}-rp-${index}-post`" v-model="rp.related_post_id" type="number" />
                </div>
                <div class="flex items-end">
                    <Button size="icon" type="button" variant="destructive" @click="$emit('remove', index)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <div class="space-y-2">
                <Label :for="`${idPrefix}-rp-${index}-reason`">{{ translations.reason }}</Label>
                <Input :id="`${idPrefix}-rp-${index}-reason`" v-model="rp.reason" />
            </div>
        </div>
    </div>
</template>
