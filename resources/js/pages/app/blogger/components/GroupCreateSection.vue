<script lang="ts" setup>
import CreateEntitySection from '@/components/blogger/CreateEntitySection.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import { useGroupForm } from '@/composables/useGroupForm';
import { useI18n } from 'vue-i18n';

defineProps<{
    canCreate: boolean;
}>();

const { t } = useI18n();
const { showCreate, createForm, openCreateForm, closeCreateForm, submitCreate } = useGroupForm();

function toggleCreate() {
    if (showCreate.value) {
        closeCreateForm();
        return;
    }

    openCreateForm();
}

defineExpose({ open: openCreateForm });
</script>

<template>
    <CreateEntitySection
        :can-create="canCreate"
        :form="createForm"
        :show-create="showCreate"
        :title="t('blogger.groups.create_section_title')"
        :tooltip-create="t('blogger.groups.create_group_tooltip')"
        :tooltip-limit="t('blogger.groups.limit_reached_tooltip')"
        @cancel="closeCreateForm"
        @submit="submitCreate"
        @toggle="toggleCreate"
    >
        <template #form="{ form, onCancel, onSubmit }">
            <GroupForm :form="form" :is-edit="false" id-prefix="new" @cancel="onCancel" @submit="onSubmit" />
        </template>
    </CreateEntitySection>
</template>
