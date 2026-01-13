<script lang="ts" setup>
import PostExtensionForm from '@/components/blogger/PostExtensionForm.vue';
import { Badge } from '@/components/ui/badge';
import { TooltipButton } from '@/components/ui/tooltip';
import type { AdminPostExtension as PostExtension } from '@/types/blog.types';
import { Pencil, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    extension: PostExtension;
    isEditing: boolean;
    editForm?: any;
}

interface Emits {
    (e: 'edit', extension: PostExtension): void;
    (e: 'submitEdit', form: any, extension: PostExtension): void;
    (e: 'applyEdit', form: any, extension: PostExtension): void;
    (e: 'cancelEdit'): void;
    (e: 'delete', extension: PostExtension): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<template>
    <div class="rounded-md border p-3">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-medium">{{ extension.title }}</div>
                <div class="mt-1 flex items-center gap-2">
                    <Badge :variant="extension.is_published ? 'success' : 'accent'">
                        {{ extension.is_published ? t('blogger.badges.published') : t('blogger.badges.draft') }}
                    </Badge>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <TooltipButton
                    :tooltip-content="isEditing ? t('blogger.extension_item.close_button') : t('blogger.extension_item.edit_button')"
                    :variant="isEditing ? 'exit' : 'toggle'"
                    size="icon"
                    @click="emit('edit', extension)"
                >
                    <X v-if="isEditing" />
                    <Pencil v-else />
                </TooltipButton>
            </div>
        </div>

        <PostExtensionForm
            v-if="isEditing"
            :extension="extension"
            :form="editForm"
            :id-prefix="`edit-ext-${extension.id}`"
            :is-edit="true"
            @apply="(form) => emit('applyEdit', form, extension)"
            @cancel="emit('cancelEdit')"
            @submit="(form) => emit('submitEdit', form, extension)"
        />
    </div>
</template>
