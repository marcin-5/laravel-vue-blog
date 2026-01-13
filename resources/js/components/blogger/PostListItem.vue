<script lang="ts" setup>
import PostExtensionManager from '@/components/blogger/PostExtensionManager.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import { Badge } from '@/components/ui/badge';
import { TooltipButton } from '@/components/ui/tooltip';
import type { AdminPostItem as PostItem } from '@/types/blog.types';
import { ChevronDown, ChevronUp, Pencil, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    post: PostItem;
    isEditing: boolean;
    editForm?: any; // External edit form instance
    isExtensionsExpanded: boolean;
}

interface Emits {
    (e: 'edit', post: PostItem): void;
    (e: 'submitEdit', form: any, post: PostItem): void;
    (e: 'cancelEdit'): void;
    (e: 'toggleExtensions', post: PostItem): void;
    (e: 'updated'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const editButtonVariant = computed(() => (props.isEditing ? 'exit' : 'toggle'));
const editButtonLabel = computed(() => (props.isEditing ? t('blogger.post_item.close_button') : t('blogger.post_item.edit_button')));

const extensionsButtonVariant = computed(() => (props.isExtensionsExpanded ? 'exit' : 'toggle'));
const extensionsButtonLabel = computed(() =>
    props.isExtensionsExpanded ? t('blogger.post_item.hide_extensions') : t('blogger.post_item.show_extensions'),
);
</script>

<template>
    <div class="rounded-md border p-3">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <div class="text-sm font-medium">{{ post.title }}</div>
                    <Badge :variant="post.is_published ? 'success' : 'accent'">
                        {{ post.is_published ? t('blogger.badges.published') : t('blogger.badges.draft') }}
                    </Badge>
                    <Badge v-if="post.visibility === 'unlisted'" variant="warning">
                        {{ t('blogger.badges.unlisted') }}
                    </Badge>
                    <Badge v-if="post.visibility === 'extension'" variant="outline">
                        {{ t('blogger.badges.extension') }}
                    </Badge>
                </div>
                <div class="text-xs text-muted-foreground">{{ post.excerpt }}</div>
            </div>
            <div class="flex items-center gap-2">
                <TooltipButton :tooltip-content="editButtonLabel" :variant="editButtonVariant" size="icon" @click="emit('edit', post)">
                    <X v-if="isEditing" />
                    <Pencil v-else />
                </TooltipButton>

                <TooltipButton
                    v-if="post.visibility !== 'extension'"
                    :tooltip-content="extensionsButtonLabel"
                    :variant="extensionsButtonVariant"
                    size="icon"
                    @click="emit('toggleExtensions', post)"
                >
                    <ChevronUp v-if="isExtensionsExpanded" />
                    <ChevronDown v-else />
                </TooltipButton>
            </div>
        </div>
        <!-- Inline Post Edit Form -->
        <PostForm
            v-if="isEditing"
            :form="editForm"
            :id-prefix="`edit-post-${post.id}`"
            :is-edit="true"
            :post="post"
            @cancel="emit('cancelEdit')"
            @submit="(form) => emit('submitEdit', form, post)"
        />

        <!-- Extensions Management -->
        <div v-if="isExtensionsExpanded" class="mt-4 ml-4 border-t pt-4">
            <PostExtensionManager :post="post" @updated="emit('updated')" />

            <div class="mt-4 flex justify-end">
                <TooltipButton
                    :tooltip-content="t('blogger.extensions.hide_list')"
                    size="icon"
                    variant="ghost"
                    @click="emit('toggleExtensions', post)"
                >
                    <ChevronUp />
                </TooltipButton>
            </div>
        </div>
    </div>
</template>
