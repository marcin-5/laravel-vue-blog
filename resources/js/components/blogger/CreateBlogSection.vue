<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import { Button } from '@/components/ui/button';
import { ensureNamespace } from '@/i18n';
import type { Category } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
await ensureNamespace(locale.value, 'blogger');

interface Props {
    canCreate: boolean;
    categories: Category[];
    showCreate: boolean;
    form?: any; // external create form
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
            <h1 class="text-xl font-semibold">{{ t('create_section.title') }}</h1>
            <div :title="!props.canCreate ? t('create_section.quota_reached_tooltip') : ''">
                <Button
                    :disabled="!props.canCreate"
                    :variant="!props.canCreate ? 'muted' : showCreate ? 'exit' : 'constructive'"
                    type="button"
                    @click="handleToggleCreate"
                >
                    <span v-if="showCreate">{{ t('create_section.close_button') }}</span>
                    <span v-else>{{ t('create_section.create_button') }}</span>
                </Button>
            </div>
        </div>

        <!-- Create New Blog Form -->
        <BlogForm
            v-if="showCreate"
            :categories="categories"
            :form="props.form"
            :is-edit="false"
            id-prefix="new"
            @cancel="handleCancelCreate"
            @submit="handleSubmitCreate"
        />
    </div>
</template>
