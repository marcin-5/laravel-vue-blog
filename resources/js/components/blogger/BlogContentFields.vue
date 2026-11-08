<script lang="ts" setup>
import EntityMarkdownField from '@/components/blogger/EntityMarkdownField.vue';
import type { BlogContentTranslations, BlogFormData } from '@/types/blog.types';
import type { InertiaForm } from '@inertiajs/vue3';

interface Props {
    form: InertiaForm<BlogFormData>;
    fieldIdPrefix: string;
    isEdit: boolean;
    translations: BlogContentTranslations;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    cancel: [];
    'update:description': [value: string | null];
    'update:landing-content': [value: string | null];
    'update:about': [value: string | null];
    'update:footer': [value: string | null];
}>();
</script>

<template>
    <div class="space-y-4">
        <EntityMarkdownField
            :id="`${props.fieldIdPrefix}-description`"
            storage-key="blog.description"
            :model-value="props.form.description"
            :error="props.form.errors.description"
            :is-edit="props.isEdit"
            :is-processing="props.form.processing"
            :label="props.translations.description.label"
            :placeholder="props.translations.description.placeholder"
            :translations="props.translations.description.markdown"
            @cancel="emit('cancel')"
            @update:model-value="emit('update:description', $event)"
        />

        <EntityMarkdownField
            :id="`${props.fieldIdPrefix}-landing-content`"
            storage-key="blog.landing_content"
            :model-value="props.form.landing_content"
            :error="props.form.errors.landing_content"
            :is-edit="props.isEdit"
            :is-processing="props.form.processing"
            :label="props.translations.landingContent.label"
            :placeholder="props.translations.landingContent.placeholder"
            :translations="props.translations.landingContent.markdown"
            @cancel="emit('cancel')"
            @update:model-value="emit('update:landing-content', $event)"
        />

        <EntityMarkdownField
            :id="`${props.fieldIdPrefix}-about`"
            storage-key="blog.about"
            :model-value="props.form.about"
            :error="props.form.errors.about"
            :is-edit="props.isEdit"
            :is-processing="props.form.processing"
            :label="props.translations.about.label"
            :placeholder="props.translations.about.placeholder"
            :translations="props.translations.about.markdown"
            @cancel="emit('cancel')"
            @update:model-value="emit('update:about', $event)"
        />

        <EntityMarkdownField
            :id="`${props.fieldIdPrefix}-footer`"
            storage-key="blog.footer"
            :model-value="props.form.footer"
            :error="props.form.errors.footer"
            :is-edit="props.isEdit"
            :is-processing="props.form.processing"
            :label="props.translations.footer.label"
            :placeholder="props.translations.footer.placeholder"
            :translations="props.translations.footer.markdown"
            @cancel="emit('cancel')"
            @update:model-value="emit('update:footer', $event)"
        />
    </div>
</template>
