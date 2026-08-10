<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import CreateEntitySection from '@/components/blogger/CreateEntitySection.vue';
import { useBlogForm } from '@/composables/useBlogForm';
import type { Category } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    canCreate: boolean;
    categories: Category[];
    showCreate: boolean;
    open: () => void;
    close: () => void;
}>();
const { t } = useI18n();
const showCreate = computed({
    get: () => props.showCreate,
    set: (value: boolean) => (value ? props.open() : props.close()),
});
const { createForm, closeCreateForm, submitCreate } = useBlogForm({ showCreate });
</script>

<template>
    <CreateEntitySection
        v-model:show-create="showCreate"
        :can-create="canCreate"
        :form="createForm"
        :title="t('blogger.create_section.title')"
        :tooltip-close="t('blogger.create_section.close_button')"
        :tooltip-create="t('blogger.create_section.create_button')"
        :tooltip-limit="t('blogger.create_section.quota_reached_tooltip')"
        @cancel="closeCreateForm"
        @submit="submitCreate"
    >
        <template #form="{ form, onCancel, onSubmit }">
            <BlogForm :categories="categories" :form="form" :is-edit="false" id-prefix="new" @cancel="onCancel" @submit="onSubmit" />
        </template>
    </CreateEntitySection>
</template>
