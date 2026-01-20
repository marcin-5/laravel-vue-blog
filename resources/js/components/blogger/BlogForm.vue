<script lang="ts" setup>
import CategorySelector from '@/components/CategorySelector.vue';
import EntityMarkdownField from '@/components/blogger/EntityMarkdownField.vue';
import EntityThemeSection from '@/components/blogger/EntityThemeSection.vue';
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import PostFormField from '@/components/blogger/PostFormField.vue';
import { useBlogFormLogic } from '@/composables/useBlogFormLogic';
import { useBloggerFormTranslations } from '@/composables/useBloggerFormTranslations';
import { useMarkdownPreviewSection } from '@/composables/useMarkdownPreviewSection';
import type { AdminBlog as Blog, BlogFormData, Category } from '@/types/blog.types';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { actionTranslations, themeSectionTranslations, themeTranslations, createMarkdownTranslations } = useBloggerFormTranslations();

interface Props {
    blog?: Blog;
    categories: Category[];
    isEdit?: boolean;
    idPrefix?: string;
    form?: InertiaForm<BlogFormData>;
}

interface Emits {
    (e: 'submit', form: any): void;
    (e: 'cancel'): void;
}

const props = withDefaults(defineProps<Props>(), {
    isEdit: false,
    idPrefix: 'blog',
});

const emit = defineEmits<Emits>();

const { form, fieldIdPrefix, updateCategories } = useBlogFormLogic({
    blog: props.blog,
    isEdit: props.isEdit,
    externalForm: props.form,
});

const baseTranslations = computed(() => ({
    name: t('blogger.form.name_label'),
    namePlaceholder: props.isEdit ? '' : t('blogger.form.name_placeholder'),
    description: t('blogger.form.description_label'),
    descriptionPlaceholder: props.isEdit ? '' : t('blogger.form.description_placeholder'),
    landingContent: t('blogger.form.landing_content_label'),
    landingContentPlaceholder: props.isEdit ? '' : t('blogger.form.landing_content_placeholder'),
    footer: t('blogger.form.footer_label'),
    footerPlaceholder: props.isEdit ? '' : t('blogger.form.footer_placeholder'),
    motto: t('blogger.form.motto_label'),
    mottoPlaceholder: props.isEdit ? '' : t('blogger.form.motto_placeholder'),
    mottoTooltip: t('blogger.form.motto_tooltip'),
}));

const descriptionTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
const landingTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
const footerTranslations = createMarkdownTranslations(useMarkdownPreviewSection());

function handleSubmit() {
    emit('submit', form);
}

function handleApply() {
    form.patch(route('blogs.update', props.blog!.id), {
        preserveScroll: true,
        preserveState: true,
    });
}

function handleCancel() {
    emit('cancel');
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <PostFormField
                :id="`${fieldIdPrefix}-name`"
                v-model="form.name"
                :error="form.errors.name"
                :label="baseTranslations.name"
                :placeholder="baseTranslations.namePlaceholder"
                required
                type="input"
            />

            <PostFormField
                :id="`${fieldIdPrefix}-motto`"
                v-model="form.motto"
                :error="form.errors.motto"
                :label="baseTranslations.motto"
                :placeholder="baseTranslations.mottoPlaceholder"
                :tooltip="baseTranslations.mottoTooltip"
                type="textarea"
            />

            <EntityMarkdownField
                :id="`${fieldIdPrefix}-description`"
                v-model="form.description"
                :error="form.errors.description"
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :label="baseTranslations.description"
                :placeholder="baseTranslations.descriptionPlaceholder"
                :translations="descriptionTranslations"
                @cancel="handleCancel"
            />

            <EntityMarkdownField
                :id="`${fieldIdPrefix}-landing-content`"
                v-model="form.landing_content as string"
                :error="form.errors.landing_content"
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :label="baseTranslations.landingContent"
                :placeholder="baseTranslations.landingContentPlaceholder"
                :translations="landingTranslations"
                @cancel="handleCancel"
            />

            <EntityMarkdownField
                :id="`${fieldIdPrefix}-footer`"
                v-model="form.footer"
                :error="form.errors.footer"
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :label="baseTranslations.footer"
                :placeholder="baseTranslations.footerPlaceholder"
                :translations="footerTranslations"
                @cancel="handleCancel"
            />

            <FormPublishingSettings
                v-model="form"
                :additional-info="props.isEdit && props.blog ? `/${props.blog.slug}` : undefined"
                :errors="form.errors"
                :id-prefix="fieldIdPrefix"
            />

            <EntityThemeSection
                v-model="form.theme"
                :errors="form.errors"
                :id-prefix="fieldIdPrefix"
                :translations="{
                    section: themeSectionTranslations,
                    theme: themeTranslations,
                }"
            />

            <CategorySelector
                :categories="props.categories"
                :id-prefix="`${fieldIdPrefix}-cat`"
                :selected-categories="form.categories"
                @update:selected-categories="updateCategories"
            />
            <div v-if="form.errors.categories" class="mt-1 text-sm font-semibold text-error">
                {{ form.errors.categories }}
            </div>

            <FormSubmitActions
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :translations="{
                    cancel: actionTranslations.cancel,
                    create: actionTranslations.create,
                    save: actionTranslations.save,
                    apply: actionTranslations.apply,
                    creating: actionTranslations.creating,
                    saving: actionTranslations.saving,
                }"
                @apply="handleApply"
                @cancel="handleCancel"
                @submit="handleSubmit"
            />
        </form>
    </div>
</template>
