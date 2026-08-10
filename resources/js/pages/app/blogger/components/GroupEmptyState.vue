<script lang="ts" setup>
import BloggerEmptyState from '@/components/blogger/BloggerEmptyState.vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    canCreate: boolean;
}>();

const emit = defineEmits<{
    create: [];
}>();

const { t } = useI18n();
</script>

<template>
    <BloggerEmptyState
        :can-create="props.canCreate"
        :empty-cta="t('blogger.groups.empty_cta')"
        :empty-text="t('blogger.groups.empty')"
        :limit-reached-hint="t('blogger.groups.limit_reached_hint')"
        @create="emit('create')"
    />
</template>
