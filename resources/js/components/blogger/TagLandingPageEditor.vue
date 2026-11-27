<script lang="ts" setup>
import EntityMarkdownField from '@/components/blogger/EntityMarkdownField.vue';
import { Button } from '@/components/ui/button';
import type { MarkdownPreviewTranslations } from '@/types/blog.types';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    blogId: number;
    tag: { id: number; slug: string; name: string; landing_page_content: string };
    translations: MarkdownPreviewTranslations;
}>();

const { t } = useI18n();
const form = useForm({ content: props.tag.landing_page_content });

function submit() {
    form.put(route('blogger.tags.landing-page.update', [props.blogId, props.tag.slug]), {
        only: ['tags'],
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <EntityMarkdownField
            :id="`tag-${props.tag.id}-landing-page`"
            v-model="form.content"
            :error="form.errors.content"
            :is-edit="true"
            :is-processing="form.processing"
            :label="t('blogger.tags.landing_page_label')"
            storage-key="blog.tag_landing_page"
            :translations="props.translations"
        />
        <Button :disabled="form.processing" type="submit" variant="constructive">
            {{ t('blogger.tags.save_landing_page') }}
        </Button>
    </form>
</template>
