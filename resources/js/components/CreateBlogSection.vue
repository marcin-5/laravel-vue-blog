<script lang="ts" setup>
import BlogForm from '@/components/BlogForm.vue';
import { Button } from '@/components/ui/button';
import type { Category } from '@/types';

interface Props {
    canCreate: boolean;
    categories: Category[];
    showCreate: boolean;
}

interface Emits {
    (e: 'toggleCreate'): void;
    (e: 'submitCreate', form: any): void;
    (e: 'cancelCreate'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function handleToggleCreate() {
    emit('toggleCreate');
}

function handleSubmitCreate(form: any) {
    emit('submitCreate', form);
}

function handleCancelCreate() {
    emit('cancelCreate');
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Your Blogs</h1>
            <div :title="!props.canCreate ? 'Maximum number of blogs reached. Please ask an admin to increase your blog quota.' : ''">
                <Button :disabled="!props.canCreate" :variant="!props.canCreate ? 'muted' : (showCreate ? 'exit' : 'constructive')" type="button" @click="handleToggleCreate">
                    <span v-if="showCreate">Close</span>
                    <span v-else>Create New Blog</span>
                </Button>
            </div>
        </div>

        <!-- Create New Blog Form -->
        <BlogForm
            v-if="showCreate"
            :categories="categories"
            :is-edit="false"
            id-prefix="new"
            @cancel="handleCancelCreate"
            @submit="handleSubmitCreate"
        />
    </div>
</template>
