<script lang="ts" setup>
import PostForm from '@/components/PostForm.vue';
import { Button } from '@/components/ui/button';
import { ensureNamespace } from '@/i18n';
import type { PostItem } from '@/types';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
await ensureNamespace(locale.value, 'blogs');

interface Props {
    post: PostItem;
    isEditing: boolean;
    editForm?: any; // External edit form instance
}

interface Emits {
    (e: 'edit', post: PostItem): void;
    (e: 'submitEdit', form: any, post: PostItem): void;
    (e: 'cancelEdit'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function handleEdit() {
    emit('edit', props.post);
}

function handleSubmitEdit(form: any) {
    emit('submitEdit', form, props.post);
}

function handleCancelEdit() {
    emit('cancelEdit');
}
</script>

<template>
    <div class="rounded-md border p-3">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-medium">{{ post.title }}</div>
                <div class="text-xs text-muted-foreground">{{ post.excerpt }}</div>
            </div>
            <div>
                <Button :variant="isEditing ? 'exit' : 'toggle'" size="sm" type="button" @click="handleEdit">
                    {{ isEditing ? $t('blogs.post_item.close_button') : $t('blogs.post_item.edit_button') }}
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
            @cancel="handleCancelEdit"
            @submit="handleSubmitEdit"
        />
    </div>
</template>
