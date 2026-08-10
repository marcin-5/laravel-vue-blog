<script lang="ts" setup>
import CategorySelector from '@/components/CategorySelector.vue';
import BlogContentFields from '@/components/blogger/BlogContentFields.vue';
import BlogSeoFields from '@/components/blogger/BlogSeoFields.vue';
import EntityThemeSection from '@/components/blogger/EntityThemeSection.vue';
import FormPublishingSettings from '@/components/blogger/FormPublishingSettings.vue';
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import BlogTagsSection from '@/components/blogger/BlogTagsSection.vue';
import { useBlogFormLogic } from '@/composables/useBlogFormLogic';
import { createApplyHandler } from '@/composables/useFormApply';
import { useBloggerFormTranslations } from '@/composables/useBloggerFormTranslations';
import { useMarkdownPreviewSection } from '@/composables/useMarkdownPreviewSection';
import type { AdminBlog as Blog, BlogFormData, Category } from '@/types/blog.types';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { actionTranslations, themeSectionTranslations, createMarkdownTranslations } = useBloggerFormTranslations();

interface Props {
    blog?: Blog;
    categories: Category[];
    isEdit?: boolean;
    idPrefix?: string;
    form?: InertiaForm<BlogFormData>;
}

interface Emits {
    submit: [form: InertiaForm<BlogFormData>];
    cancel: [];
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

const seoTranslations = computed(() => ({
    name: t('blogger.form.name_label'),
    namePlaceholder: props.isEdit ? '' : t('blogger.form.name_placeholder'),
    seoTitle: t('blogger.form.seo_title_label'),
    seoTitlePlaceholder: t('blogger.form.seo_title_placeholder'),
    seoDescription: t('blogger.form.seo_description_label'),
    seoDescriptionPlaceholder: t('blogger.form.seo_description_placeholder'),
    aboutSeoDescription: t('blogger.form.about_seo_description_label'),
    aboutSeoDescriptionPlaceholder: t('blogger.form.about_seo_description_placeholder'),
    contactSeoDescription: t('blogger.form.contact_seo_description_label'),
    contactSeoDescriptionPlaceholder: t('blogger.form.contact_seo_description_placeholder'),
    motto: t('blogger.form.motto_label'),
    mottoPlaceholder: props.isEdit ? '' : t('blogger.form.motto_placeholder'),
    mottoTooltip: t('blogger.form.motto_tooltip'),
    characters: t('blogger.post_form.characters'),
}));

const descriptionMarkdownTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
const landingMarkdownTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
const aboutMarkdownTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
const footerMarkdownTranslations = createMarkdownTranslations(useMarkdownPreviewSection());

const contentTranslations = computed(() => ({
    description: {
        label: t('blogger.form.description_label'),
        placeholder: props.isEdit ? '' : t('blogger.form.description_placeholder'),
        markdown: descriptionMarkdownTranslations.value,
    },
    landingContent: {
        label: t('blogger.form.landing_content_label'),
        placeholder: props.isEdit ? '' : t('blogger.form.landing_content_placeholder'),
        markdown: landingMarkdownTranslations.value,
    },
    about: {
        label: t('blogger.form.about_label'),
        placeholder: props.isEdit ? '' : t('blogger.form.about_placeholder'),
        markdown: aboutMarkdownTranslations.value,
    },
    footer: {
        label: t('blogger.form.footer_label'),
        placeholder: props.isEdit ? '' : t('blogger.form.footer_placeholder'),
        markdown: footerMarkdownTranslations.value,
    },
}));

function handleSubmit() {
    emit('submit', form);
}

const handleApply = createApplyHandler(form, 'blogs.update', props.blog?.id ?? 0);

function handleCancel() {
    emit('cancel');
}
</script>

<template>
    <div class="rounded-md border border-sidebar-border/70 p-4">
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <BlogSeoFields :field-id-prefix="fieldIdPrefix" :form="form" :translations="seoTranslations" />

            <BlogContentFields
                :field-id-prefix="fieldIdPrefix"
                :form="form"
                :is-edit="props.isEdit"
                :translations="contentTranslations"
                @cancel="handleCancel"
            />

            <FormPublishingSettings
                v-model="form"
                :additional-info="props.isEdit && props.blog ? `/${props.blog.slug}` : undefined"
                :errors="form.errors"
                :id-prefix="fieldIdPrefix"
            />

            <EntityThemeSection v-model="form.theme" :errors="form.errors" :id-prefix="fieldIdPrefix" :translations="themeSectionTranslations" />

            <CategorySelector
                :categories="props.categories"
                :id-prefix="`${fieldIdPrefix}-cat`"
                :selected-categories="form.categories"
                @update:selected-categories="updateCategories"
            />
            <div v-if="form.errors.categories" class="mt-1 text-sm font-semibold text-error">
                {{ form.errors.categories }}
            </div>

            <BlogTagsSection v-if="props.isEdit && props.blog" :blog-id="props.blog.id" :id-prefix="`${fieldIdPrefix}-tags`" />

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
