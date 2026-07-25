<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import CreateEntitySection from '@/components/blogger/CreateEntitySection.vue';
import { useBlogForm } from '@/composables/useBlogForm';
import type { Category } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

defineProps<{
    canCreate: boolean;
    categories: Category[];
}>();

const { t } = useI18n();
const { showCreate, createForm, openCreateForm, closeCreateForm, submitCreate } = useBlogForm();

function toggleCreate() {
    if (showCreate.value) {
        closeCreateForm();
        return;
    }

    openCreateForm();
}

defineExpose({ open: openCreateForm });
</script>

<template>
    <CreateEntitySection
        :can-create="canCreate"
        :form="createForm"
        :show-create="showCreate"
        :title="t('blogger.create_section.title')"
        :tooltip-close="t('blogger.create_section.close_button')"
        :tooltip-create="t('blogger.create_section.create_button')"
        :tooltip-limit="t('blogger.create_section.quota_reached_tooltip')"
        @cancel="closeCreateForm"
        @submit="submitCreate"
        @toggle="toggleCreate"
    >
        <template #form="{ form, onCancel, onSubmit }">
            <BlogForm :categories="categories" :form="form" :is-edit="false" id-prefix="new" @cancel="onCancel" @submit="onSubmit" />
        </template>
    </CreateEntitySection>
</template>
