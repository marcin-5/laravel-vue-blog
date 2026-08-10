<script lang="ts" setup>
import PostFormField from '@/components/blogger/PostFormField.vue';
import { useSeoLengthClasses } from '@/composables/useSeoLengthClasses';
import type { BlogFormData } from '@/types/blog.types';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SeoTranslations {
    name: string;
    namePlaceholder: string;
    seoTitle: string;
    seoTitlePlaceholder: string;
    seoDescription: string;
    seoDescriptionPlaceholder: string;
    aboutSeoDescription: string;
    aboutSeoDescriptionPlaceholder: string;
    contactSeoDescription: string;
    contactSeoDescriptionPlaceholder: string;
    motto: string;
    mottoPlaceholder: string;
    mottoTooltip: string;
    characters: string;
}

interface Props {
    form: InertiaForm<BlogFormData>;
    fieldIdPrefix: string;
    translations: SeoTranslations;
}

const props = defineProps<Props>();

const { getRangeClass, getThresholdClass } = useSeoLengthClasses();

const seoTitleClass = computed(() => getRangeClass(props.form.seo_title, 50, 60));
const seoDescriptionClass = computed(() => getThresholdClass(props.form.seo_description, 120, 160));
const aboutSeoDescriptionClass = computed(() => getThresholdClass(props.form.about_seo_description, 60, 160));
const contactSeoDescriptionClass = computed(() => getThresholdClass(props.form.contact_seo_description, 60, 160));
</script>

<template>
    <div class="space-y-4">
        <PostFormField
            :id="`${props.fieldIdPrefix}-name`"
            v-model="props.form.name"
            :error="props.form.errors.name"
            :label="props.translations.name"
            :placeholder="props.translations.namePlaceholder"
            required
            type="input"
        />

        <PostFormField
            :id="`${props.fieldIdPrefix}-seo-title`"
            v-model="props.form.seo_title"
            :error="props.form.errors.seo_title"
            :hint="`${props.form.seo_title?.length || 0} ${props.translations.characters}`"
            :input-class="seoTitleClass"
            :label="props.translations.seoTitle"
            :placeholder="props.translations.seoTitlePlaceholder"
            type="input"
        />

        <PostFormField
            :id="`${props.fieldIdPrefix}-seo-description`"
            v-model="props.form.seo_description"
            :error="props.form.errors.seo_description"
            :hint="`${props.form.seo_description?.length || 0} ${props.translations.characters}`"
            :input-class="seoDescriptionClass"
            :label="props.translations.seoDescription"
            :placeholder="props.translations.seoDescriptionPlaceholder"
            :rows="2"
            type="textarea"
        />

        <PostFormField
            :id="`${props.fieldIdPrefix}-about-seo-description`"
            v-model="props.form.about_seo_description"
            :error="props.form.errors.about_seo_description"
            :hint="`${props.form.about_seo_description?.length || 0} ${props.translations.characters}`"
            :input-class="aboutSeoDescriptionClass"
            :label="props.translations.aboutSeoDescription"
            :placeholder="props.translations.aboutSeoDescriptionPlaceholder"
            :rows="2"
            type="textarea"
        />

        <PostFormField
            :id="`${props.fieldIdPrefix}-contact-seo-description`"
            v-model="props.form.contact_seo_description"
            :error="props.form.errors.contact_seo_description"
            :hint="`${props.form.contact_seo_description?.length || 0} ${props.translations.characters}`"
            :input-class="contactSeoDescriptionClass"
            :label="props.translations.contactSeoDescription"
            :placeholder="props.translations.contactSeoDescriptionPlaceholder"
            :rows="2"
            type="textarea"
        />

        <PostFormField
            :id="`${props.fieldIdPrefix}-motto`"
            v-model="props.form.motto"
            :error="props.form.errors.motto"
            :label="props.translations.motto"
            :placeholder="props.translations.mottoPlaceholder"
            :tooltip="props.translations.mottoTooltip"
            type="textarea"
        />
    </div>
</template>
