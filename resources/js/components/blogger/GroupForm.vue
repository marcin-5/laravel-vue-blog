<script lang="ts" setup>
import EntityMarkdownField from '@/components/blogger/EntityMarkdownField.vue';
import EntityThemeSection from '@/components/blogger/EntityThemeSection.vue';
import FormPublishingSettings from '@/components/blogger/FormPublishingSettings.vue';
import FormSubmitActions from '@/components/blogger/FormSubmitActions.vue';
import PostFormField from '@/components/blogger/PostFormField.vue';
import { useBloggerFormTranslations } from '@/composables/useBloggerFormTranslations';
import { useGroupFormLogic } from '@/composables/useGroupFormLogic';
import { useMarkdownPreviewSection } from '@/composables/useMarkdownPreviewSection';
import type { AdminGroup as Group, GroupFormData } from '@/types/blog.types';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { actionTranslations, themeSectionTranslations, themeTranslations, createMarkdownTranslations } = useBloggerFormTranslations();

interface Props {
    group?: Group;
    isEdit?: boolean;
    idPrefix?: string;
    form?: InertiaForm<GroupFormData>;
}

interface Emits {
    (e: 'submit', form: any): void;
    (e: 'cancel'): void;
}

const props = withDefaults(defineProps<Props>(), {
    isEdit: false,
    idPrefix: 'group',
});

const emit = defineEmits<Emits>();

const { form, fieldIdPrefix } = useGroupFormLogic({
    group: props.group,
    isEdit: props.isEdit,
    externalForm: props.form,
});

const baseTranslations = computed(() => ({
    name: t('blogger.group_form.name_label'),
    namePlaceholder: props.isEdit ? '' : t('blogger.group_form.name_placeholder'),
    content: t('blogger.group_form.content_label'),
    contentPlaceholder: props.isEdit ? '' : t('blogger.group_form.content_placeholder'),
    footer: t('blogger.group_form.footer_label'),
    footerPlaceholder: props.isEdit ? '' : t('blogger.group_form.footer_placeholder'),
}));

const contentTranslations = createMarkdownTranslations(useMarkdownPreviewSection());
const footerTranslations = createMarkdownTranslations(useMarkdownPreviewSection());

function handleSubmit() {
    emit('submit', form);
}

function handleApply() {
    if (!props.group?.id) return;
    form.patch(route('groups.update', props.group.id), {
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

            <EntityMarkdownField
                :id="`${fieldIdPrefix}-content`"
                v-model="form.content"
                :error="form.errors.content"
                :is-edit="props.isEdit"
                :is-processing="form.processing"
                :label="baseTranslations.content"
                :placeholder="baseTranslations.contentPlaceholder"
                :translations="contentTranslations"
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

            <FormPublishingSettings v-model="form" :errors="form.errors" :id-prefix="fieldIdPrefix" />

            <EntityThemeSection
                v-model="form.theme"
                :errors="form.errors"
                :id-prefix="fieldIdPrefix"
                :translations="{
                    section: themeSectionTranslations,
                    theme: themeTranslations,
                }"
            />

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
