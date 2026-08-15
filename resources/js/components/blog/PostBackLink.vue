<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    landingUrl: string;
}>();

const { t } = useI18n();

const navigateBack = () => {
    router.visit(props.landingUrl);
};
</script>

<template>
    <div class="flex items-center">
        <button
            class="inline-flex items-center rounded-sm border border-border bg-card px-3 py-2 text-sm text-primary transition-colors hover:bg-secondary"
            @click="navigateBack"
        >
            <ArrowLeft class="mr-2 size-4" />
            <span>{{ t('blog.post_nav.back') }}</span>
        </button>
    </div>
</template>
