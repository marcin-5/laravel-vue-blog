<script lang="ts" setup>
import CreateEntitySection from '@/components/blogger/CreateEntitySection.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import { useGroupForm } from '@/composables/useGroupForm';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    canCreate: boolean;
    showCreate: boolean;
    open: () => void;
    close: () => void;
}>();

const { t } = useI18n();
const showCreate = computed({
    get: () => props.showCreate,
    set: (value: boolean) => (value ? props.open() : props.close()),
});
const { createForm, closeCreateForm, submitCreate } = useGroupForm({ showCreate });
</script>

<template>
    <CreateEntitySection
        v-model:show-create="showCreate"
        :can-create="canCreate"
        :form="createForm"
        :title="t('blogger.groups.create_section_title')"
        :tooltip-create="t('blogger.groups.create_group_tooltip')"
        :tooltip-limit="t('blogger.groups.limit_reached_tooltip')"
        @cancel="closeCreateForm"
        @submit="submitCreate"
    >
        <template #form="{ form, onCancel, onSubmit }">
            <GroupForm :form="form" :is-edit="false" id-prefix="new" @cancel="onCancel" @submit="onSubmit" />
        </template>
    </CreateEntitySection>
</template>
