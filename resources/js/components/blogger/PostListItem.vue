<script lang="ts" setup>
import PostExtensionForm from '@/components/blogger/PostExtensionForm.vue';
import PostExtensionListItem from '@/components/blogger/PostExtensionListItem.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import { Button } from '@/components/ui/button';
import type { AdminPostExtension as PostExtension, AdminPostItem as PostItem } from '@/types/blog.types';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
    post: PostItem;
    isEditing: boolean;
    editForm?: any; // External edit form instance
    isExtensionsExpanded: boolean;
    creatingExtensionId?: number | null;
    editingExtensionId?: number | null;
    extensionForm?: any;
    extensionEditForm?: any;
}

interface Emits {
    (e: 'edit', post: PostItem): void;
    (e: 'submitEdit', form: any, post: PostItem): void;
    (e: 'cancelEdit'): void;
    (e: 'toggleExtensions', post: PostItem): void;
    (e: 'createExtension', post: PostItem): void;
    (e: 'submitCreateExtension', form: any, post: PostItem): void;
    (e: 'cancelCreateExtension'): void;
    (e: 'editExtension', extension: PostExtension): void;
    (e: 'submitEditExtension', form: any, extension: PostExtension): void;
    (e: 'applyEditExtension', form: any, extension: PostExtension): void;
    (e: 'cancelEditExtension'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const editButtonVariant = computed(() => (props.isEditing ? 'exit' : 'toggle'));
const editButtonLabel = computed(() => (props.isEditing ? t('blogger.post_item.close_button') : t('blogger.post_item.edit_button')));

const extensionsButtonVariant = computed(() => (props.isExtensionsExpanded ? 'exit' : 'toggle'));
const extensionsButtonLabel = computed(() =>
    props.isExtensionsExpanded ? t('blogger.post_item.hide_extensions') : t('blogger.post_item.show_extensions'),
);

const addExtensionButtonVariant = computed(() => (props.creatingExtensionId === props.post.id ? 'exit' : 'constructive'));
const addExtensionButtonLabel = computed(() =>
    props.creatingExtensionId === props.post.id ? t('blogger.post_item.close_button') : t('blogger.post_item.add_extension'),
);
</script>

<template>
    <div class="rounded-md border p-3">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-medium">{{ post.title }}</div>
                <div class="text-xs text-muted-foreground">{{ post.excerpt }}</div>
            </div>
            <div class="flex items-center gap-2">
                <Button :variant="editButtonVariant" size="sm" type="button" @click="emit('edit', post)">
                    {{ editButtonLabel }}
                </Button>
                <Button :variant="extensionsButtonVariant" size="sm" type="button" @click="emit('toggleExtensions', post)">
                    {{ extensionsButtonLabel }}
                </Button>
                <Button :variant="addExtensionButtonVariant" size="sm" type="button" @click="emit('createExtension', post)">
                    {{ addExtensionButtonLabel }}
                </Button>
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

        <!-- Extensions List -->
        <div v-if="isExtensionsExpanded" class="mt-4 ml-4 border-t pt-4">
            <div v-if="post.extensions && post.extensions.length" class="space-y-3">
                <PostExtensionListItem
                    v-for="extension in post.extensions"
                    :key="`ext-${post.id}-${extension.id}`"
                    :edit-form="extensionEditForm"
                    :extension="extension"
                    :is-editing="editingExtensionId === extension.id"
                    @edit="emit('editExtension', $event)"
                    @apply-edit="emit('applyEditExtension', $event, extension)"
                    @cancel-edit="emit('cancelEditExtension')"
                    @submit-edit="emit('submitEditExtension', $event, extension)"
                />
            </div>
            <div v-else class="text-sm text-muted-foreground">{{ t('blogger.extensions.empty') }}</div>

            <!-- Inline Create Extension Form (under list) -->
            <PostExtensionForm
                v-if="creatingExtensionId === post.id"
                :form="extensionForm"
                :id-prefix="`ext-${post.id}`"
                :is-edit="false"
                :post-id="post.id"
                @cancel="emit('cancelCreateExtension')"
                @submit="(form) => emit('submitCreateExtension', form, post)"
            />

            <!-- Collapse Button at the bottom of the list -->
            <div class="mt-4 flex justify-end">
                <Button size="sm" variant="ghost" @click="emit('toggleExtensions', post)">
                    {{ t('blogger.extensions.hide_list') }}
                </Button>
            </div>
        </div>
    </div>
</template>
