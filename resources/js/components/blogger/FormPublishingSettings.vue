<script lang="ts" setup>
import FormCheckboxField from '@/components/blogger/FormCheckboxField.vue';
import FormNumberField from '@/components/blogger/FormNumberField.vue';
import FormSelectField from '@/components/blogger/FormSelectField.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    modelValue: {
        is_published: boolean;
        locale: string;
        sidebar: number;
        page_size: number;
    };
    errors: Record<string, string>;
    idPrefix: string;
    additionalInfo?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:modelValue', value: Props['modelValue']): void;
}>();

const { t } = useI18n();

const translations = computed(() => ({
    published: t('blogger.form.published_label'),
    locale: t('blogger.form.locale_label'),
    sidebar: t('blogger.form.sidebar_label'),
    sidebarHint: t('blogger.form.sidebar_hint'),
    pageSize: t('blogger.form.page_size_label'),
}));

const localeOptions = [
    { value: 'en', label: 'EN' },
    { value: 'pl', label: 'PL' },
];

const model = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <FormCheckboxField
                :id="`${idPrefix}-published`"
                v-model="model.is_published"
                :additional-info="additionalInfo"
                :error="errors.is_published"
                :label="translations.published"
            />
            <div class="ml-auto">
                <FormSelectField
                    :id="`${idPrefix}-locale`"
                    v-model="model.locale"
                    :error="errors.locale"
                    :label="translations.locale"
                    :options="localeOptions"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormNumberField
                :id="`${idPrefix}-sidebar`"
                v-model="model.sidebar"
                :error="errors.sidebar"
                :hint="translations.sidebarHint"
                :label="translations.sidebar"
                :max="50"
                :min="-50"
            />
            <FormNumberField
                :id="`${idPrefix}-page_size`"
                v-model="model.page_size"
                :error="errors.page_size"
                :label="translations.pageSize"
                :max="100"
                :min="1"
            />
        </div>
    </div>
</template>
