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
        :empty-cta="t('blogger.empty_cta')"
        :empty-text="t('blogger.empty')"
        :limit-reached-hint="t('blogger.limit_reached_hint')"
        @create="emit('create')"
    />
</template>
