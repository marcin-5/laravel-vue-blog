<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import CreateSection from '@/components/blogger/CreateSection.vue';
import type { Category } from '@/types/blog.types';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    canCreate: boolean;
    categories: Category[];
    showCreate: boolean;
    form?: any;
}>();

const emit = defineEmits<{
    (e: 'toggleCreate'): void;
    (e: 'submitCreate', form: any): void;
    (e: 'cancelCreate'): void;
}>();
</script>

<template>
    <CreateSection
        :can-create="canCreate"
        :show-create="showCreate"
        :title="t('blogger.create_section.title')"
        :tooltip-close="t('blogger.create_section.close_button')"
        :tooltip-create="t('blogger.create_section.create_button')"
        :tooltip-limit="t('blogger.create_section.quota_reached_tooltip')"
        @toggle="emit('toggleCreate')"
    >
        <template #form>
            <BlogForm
                :categories="categories"
                :form="form"
                :is-edit="false"
                id-prefix="new"
                @cancel="emit('cancelCreate')"
                @submit="(f) => emit('submitCreate', f)"
            />
        </template>
    </CreateSection>
</template>
