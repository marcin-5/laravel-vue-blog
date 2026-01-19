<script lang="ts" setup>
import CreateSection from '@/components/blogger/CreateSection.vue';
import GroupForm from '@/components/blogger/GroupForm.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    canCreate: boolean;
    showCreate: boolean;
    form?: any;
}>();

const emit = defineEmits<{
    (e: 'toggleCreate'): void;
    (e: 'submitCreate', form: any): void;
    (e: 'cancelCreate'): void;
}>();
</script>

<template>
    <CreateSection
        :can-create="canCreate"
        :show-create="showCreate"
        :title="t('blogger.groups.create_section_title')"
        :tooltip-create="t('blogger.groups.create_group_tooltip')"
        :tooltip-limit="t('blogger.groups.limit_reached_tooltip')"
        @toggle="emit('toggleCreate')"
    >
        <template #form>
            <GroupForm :form="form" :is-edit="false" id-prefix="new" @cancel="emit('cancelCreate')" @submit="(f) => emit('submitCreate', f)" />
        </template>
    </CreateSection>
</template>
