<script lang="ts" setup>
import EntityMarkdownField from '@/components/blogger/EntityMarkdownField.vue';
import type { BlogFormData } from '@/types/blog.types';
import type { InertiaForm } from '@inertiajs/vue3';

interface MarkdownTranslations {
    cancel: string;
    create: string;
    save: string;
    exitPreview: string;
    markdownLabel: string;
    previewLabel: string;
    previewModeTitle: string;
    toggleLayout: string;
    closePreview: string;
    preview: string;
    fullPreview: string;
    splitView: string;
}

interface ContentFieldTranslations {
    label: string;
    placeholder: string;
    markdown: MarkdownTranslations;
}

interface ContentTranslations {
    description: ContentFieldTranslations;
    landingContent: ContentFieldTranslations;
    about: ContentFieldTranslations;
    footer: ContentFieldTranslations;
}

interface Props {
    form: InertiaForm<BlogFormData>;
    fieldIdPrefix: string;
    isEdit: boolean;
    translations: ContentTranslations;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    cancel: [];
}>();
</script>

<template>
    <div class="space-y-4">
        <EntityMarkdownField
            :id="`${props.fieldIdPrefix}-description`"
            v-model="props.form.description"
            :error="props.form.errors.description"
            :is-edit="props.isEdit"
            :is-processing="props.form.processing"
            :label="props.translations.description.label"
            :placeholder="props.translations.description.placeholder"
            :translations="props.translations.description.markdown"
            @cancel="emit('cancel')"
        />

        <EntityMarkdownField
            :id="`${props.fieldIdPrefix}-landing-content`"
            v-model="props.form.landing_content"
            :error="props.form.errors.landing_content"
            :is-edit="props.isEdit"
            :is-processing="props.form.processing"
            :label="props.translations.landingContent.label"
            :placeholder="props.translations.landingContent.placeholder"
            :translations="props.translations.landingContent.markdown"
            @cancel="emit('cancel')"
        />

        <EntityMarkdownField
            :id="`${props.fieldIdPrefix}-about`"
            v-model="props.form.about"
            :error="props.form.errors.about"
            :is-edit="props.isEdit"
            :is-processing="props.form.processing"
            :label="props.translations.about.label"
            :placeholder="props.translations.about.placeholder"
            :translations="props.translations.about.markdown"
            @cancel="emit('cancel')"
        />

        <EntityMarkdownField
            :id="`${props.fieldIdPrefix}-footer`"
            v-model="props.form.footer"
            :error="props.form.errors.footer"
            :is-edit="props.isEdit"
            :is-processing="props.form.processing"
            :label="props.translations.footer.label"
            :placeholder="props.translations.footer.placeholder"
            :translations="props.translations.footer.markdown"
            @cancel="emit('cancel')"
        />
    </div>
</template>
