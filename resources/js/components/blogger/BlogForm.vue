<script lang="ts" setup>
import CategorySelector from '@/components/CategorySelector.vue';
import EntityMarkdownField from '@/components/blogger/EntityMarkdownField.vue';
import EntityThemeSection from '@/components/blogger/EntityThemeSection.vue';
import FormPublishingSettings from '@/components/blogger/FormPublishingSettings.vue';
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import PostFormField from '@/components/blogger/PostFormField.vue';
import BlogTagsSection from '@/components/blogger/BlogTagsSection.vue';
import { useBlogFormLogic } from '@/composables/useBlogFormLogic';
import { useBloggerFormTranslations } from '@/composables/useBloggerFormTranslations';
import { useMarkdownPreviewSection } from '@/composables/useMarkdownPreviewSection';
import { useSeoLengthClasses } from '@/composables/useSeoLengthClasses';
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
    seoTitle: t('blogger.form.seo_title_label'),
    seoTitlePlaceholder: t('blogger.form.seo_title_placeholder'),
    seoDescription: t('blogger.form.seo_description_label'),
    seoDescriptionPlaceholder: t('blogger.form.seo_description_placeholder'),
    aboutSeoDescription: t('blogger.form.about_seo_description_label'),
    aboutSeoDescriptionPlaceholder: t('blogger.form.about_seo_description_placeholder'),
    contactSeoDescription: t('blogger.form.contact_seo_description_label'),
    contactSeoDescriptionPlaceholder: t('blogger.form.contact_seo_description_placeholder'),
    description: t('blogger.form.description_label'),
    descriptionPlaceholder: props.isEdit ? '' : t('blogger.form.description_placeholder'),
    landingContent: t('blogger.form.landing_content_label'),
    landingContentPlaceholder: props.isEdit ? '' : t('blogger.form.landing_content_placeholder'),
    footer: t('blogger.form.footer_label'),
    footerPlaceholder: props.isEdit ? '' : t('blogger.form.footer_placeholder'),
    about: t('blogger.form.about_label'),
    aboutPlaceholder: props.isEdit ? '' : t('blogger.form.about_placeholder'),
    motto: t('blogger.form.motto_label'),
    mottoPlaceholder: props.isEdit ? '' : t('blogger.form.motto_placeholder'),
    mottoTooltip: t('blogger.form.motto_tooltip'),
    characters: t('blogger.post_form.characters'),
}));

const descriptionTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
const landingTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
const aboutTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
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

const { getRangeClass, getThresholdClass } = useSeoLengthClasses();

const seoTitleClass = computed(() => getRangeClass(form.seo_title, 50, 60));
const seoDescriptionClass = computed(() => getThresholdClass(form.seo_description, 120, 160));
const aboutSeoDescriptionClass = computed(() => getThresholdClass(form.about_seo_description, 60, 160));
const contactSeoDescriptionClass = computed(() => getThresholdClass(form.contact_seo_description, 60, 160));
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
                :id="`${fieldIdPrefix}-seo-title`"
                v-model="form.seo_title"
                :error="form.errors.seo_title"
                :hint="`${form.seo_title?.length || 0} ${baseTranslations.characters}`"
                :input-class="seoTitleClass"
                :label="baseTranslations.seoTitle"
                :placeholder="baseTranslations.seoTitlePlaceholder"
                type="input"
            />

            <PostFormField
                :id="`${fieldIdPrefix}-seo-description`"
                v-model="form.seo_description"
                :error="form.errors.seo_description"
                :hint="`${form.seo_description?.length || 0} ${baseTranslations.characters}`"
                :input-class="seoDescriptionClass"
                :label="baseTranslations.seoDescription"
                :placeholder="baseTranslations.seoDescriptionPlaceholder"
                :rows="2"
                type="textarea"
            />

            <PostFormField
                :id="`${fieldIdPrefix}-about-seo-description`"
                v-model="form.about_seo_description"
                :error="form.errors.about_seo_description"
                :hint="`${form.about_seo_description?.length || 0} ${baseTranslations.characters}`"
                :input-class="aboutSeoDescriptionClass"
                :label="baseTranslations.aboutSeoDescription"
                :placeholder="baseTranslations.aboutSeoDescriptionPlaceholder"
                :rows="2"
                type="textarea"
            />

            <PostFormField
                :id="`${fieldIdPrefix}-contact-seo-description`"
                v-model="form.contact_seo_description"
                :error="form.errors.contact_seo_description"
                :hint="`${form.contact_seo_description?.length || 0} ${baseTranslations.characters}`"
                :input-class="contactSeoDescriptionClass"
                :label="baseTranslations.contactSeoDescription"
                :placeholder="baseTranslations.contactSeoDescriptionPlaceholder"
                :rows="2"
                type="textarea"
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
                v-model="form.landing_content"
                :error="form.errors.landing_content"
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :label="baseTranslations.landingContent"
                :placeholder="baseTranslations.landingContentPlaceholder"
                :translations="landingTranslations"
                @cancel="handleCancel"
            />

            <EntityMarkdownField
                :id="`${fieldIdPrefix}-about`"
                v-model="form.about"
                :error="form.errors.about"
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :label="baseTranslations.about"
                :placeholder="baseTranslations.aboutPlaceholder"
                :translations="aboutTranslations"
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
