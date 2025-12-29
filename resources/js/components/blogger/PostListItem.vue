<script lang="ts" setup>
import PostForm from '@/components/blogger/PostForm.vue';
import { Button } from '@/components/ui/button';
import { useI18nNs } from '@/composables/useI18nNs';
import type { AdminPostItem as PostItem } from '@/types/blog.types';
import { computed } from 'vue';

const { t } = await useI18nNs('blogger');

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

const editButtonVariant = computed(() => (props.isEditing ? 'exit' : 'toggle'));
const editButtonLabel = computed(() => (props.isEditing ? t('blogger.post_item.close_button') : t('blogger.post_item.edit_button')));
</script>

<template>
    <div class="rounded-md border p-3">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-medium">{{ post.title }}</div>
                <div class="text-xs text-muted-foreground">{{ post.excerpt }}</div>
            </div>
            <div>
                <Button :variant="editButtonVariant" size="sm" type="button" @click="emit('edit', post)">
                    {{ editButtonLabel }}
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
    </div>
</template>
