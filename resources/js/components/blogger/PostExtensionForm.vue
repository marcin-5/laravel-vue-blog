<script lang="ts" setup>
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import MarkdownPreviewSection from '@/components/blogger/MarkdownPreviewSection.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useMarkdownPreviewSection } from '@/composables/useMarkdownPreviewSection';
import type { AdminPostExtension as PostExtension } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    extension?: PostExtension;
    postId?: number;
    isEdit?: boolean;
    idPrefix?: string;
    form?: any;
}

interface Emits {
    (e: 'submit', form: any): void;
    (e: 'apply', form: any): void;
    (e: 'cancel'): void;
}

const props = withDefaults(defineProps<Props>(), {
    isEdit: false,
    idPrefix: 'extension',
});

const emit = defineEmits<Emits>();

const { isPreviewMode, isFullPreview, previewLayout, previewHtml, togglePreview, toggleFullPreview, setLayout, handleInput } =
    useMarkdownPreviewSection();

const form =
    props.form ||
    useForm({
        title: props.extension?.title || '',
        content: props.extension?.content || '',
        is_published: props.extension?.is_published || false,
    });

const fieldIdPrefix = computed(() => props.idPrefix);

const translationKeys = computed(() => ({
    title: t('blogger.extension_form.title_label'),
    titlePlaceholder: t('blogger.extension_form.title_placeholder'),
    content: t('blogger.extension_form.content_label'),
    contentPlaceholder: t('blogger.extension_form.content_placeholder'),
    published: t('blogger.extension_form.published_label'),
    cancel: t('blogger.extension_form.cancel_button'),
    create: t('blogger.extension_form.create_button'),
    creating: t('blogger.extension_form.creating_button'),
    save: t('blogger.extension_form.save_button'),
    apply: t('blogger.extension_form.apply_button'),
    saving: t('blogger.extension_form.saving_button'),
    preview: t('blogger.extension_form.preview_button'),
    closePreview: t('blogger.extension_form.close_button'),
    fullPreview: t('blogger.extension_form.full_preview_button'),
    splitView: t('blogger.extension_form.split_view_button'),
    toggleLayout: previewLayout.value === 'vertical' ? t('blogger.extension_form.horizontal_button') : t('blogger.extension_form.vertical_button'),
    exitPreview: t('blogger.extension_form.exit_preview_button'),
    markdownLabel: t('blogger.extension_form.markdown_label'),
    previewLabel: t('blogger.extension_form.preview_label'),
    previewModeTitle: t('blogger.extension_form.preview_mode_title'),
}));

watch(
    () => props.extension,
    (newExt) => {
        if (newExt && !props.form) {
            form.title = newExt.title;
            form.content = newExt.content;
            form.is_published = newExt.is_published;
        }
    },
    { immediate: true },
);

function handleSubmit() {
    emit('submit', form);
}
</script>

<template>
    <form class="mt-4 space-y-4 border-t pt-4" @submit.prevent="handleSubmit">
        <div class="space-y-2">
            <Label :for="`${fieldIdPrefix}-title`">{{ translationKeys.title }}</Label>
            <Input :id="`${fieldIdPrefix}-title`" v-model="form.title" :placeholder="translationKeys.titlePlaceholder" required />
            <div v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</div>
        </div>

        <MarkdownPreviewSection
            :id="`${fieldIdPrefix}-content`"
            :error="form.errors.content"
            :is-full-preview="isFullPreview"
            :is-preview-mode="isPreviewMode"
            :label="translationKeys.content"
            :model-value="form.content"
            :placeholder="translationKeys.contentPlaceholder"
            :preview-html="previewHtml"
            :preview-layout="previewLayout"
            :translations="translationKeys"
            @input="handleInput(form.content)"
            @set-layout="(layout) => setLayout(layout)"
            @toggle-full-preview="toggleFullPreview"
            @toggle-preview="togglePreview"
            @update:model-value="(val) => (form.content = val)"
        />

        <div class="flex items-center space-x-2">
            <input :id="`${fieldIdPrefix}-is-published`" v-model="form.is_published" type="checkbox" />
            <Label
                :for="`${fieldIdPrefix}-is-published`"
                class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
            >
                {{ translationKeys.published }}
            </Label>
        </div>

        <FormSubmitActions
            :is-edit="isEdit"
            :is-processing="form.processing"
            :translations="translationKeys"
            @apply="emit('apply', form)"
            @cancel="emit('cancel')"
            @submit="handleSubmit"
        />
    </form>
</template>
