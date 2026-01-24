<script lang="ts" setup>
import BlogForm from '@/components/blogger/BlogForm.vue';
import BloggerListItem from '@/components/blogger/BloggerListItem.vue';
import ItemActionGroup from '@/components/blogger/ItemActionGroup.vue';
import PostForm from '@/components/blogger/PostForm.vue';
import type {
    AdminBlog as Blog,
    AdminPostItem as PostItem,
    Category,
    ListItemEmits,
    ListItemProps
} from '@/types/blog.types';
import { localizedName } from '@/utils/localization';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

function handleReload() {
    router.reload({ only: ['blogs'] });
}

interface Props extends ListItemProps<Blog> {
    categories: Category[];
}

const props = defineProps<Props>();
const emit = defineEmits<ListItemEmits<Blog, PostItem>>();

function handleSubmitEdit(form: any) {
    emit('submitEdit', form, props.item);
}

function handleSubmitCreatePost(form: any) {
    emit('submitCreatePost', form);
}

function handleSubmitEditPost(form: any, post: PostItem) {
    emit('submitEditPost', form, post);
}
</script>

<template>
    <BloggerListItem
        :subtitle="`/${item.slug} · ${item.creation_date ?? ''}`"
        v-bind="props"
        @edit="(i) => emit('edit', i)"
        @apply-edit-extension="(form, ext) => emit('applyEditExtension', form, ext)"
        @cancel-create-extension="emit('cancelCreateExtension')"
        @cancel-edit="emit('cancelEdit')"
        @cancel-edit-extension="emit('cancelEditExtension')"
        @cancel-edit-post="emit('cancelEditPost')"
        @cancel-create-post="emit('cancelCreatePost')"
        @create-extension="(p) => emit('createExtension', p)"
        @create-post="(i) => emit('createPost', i)"
        @edit-extension="(ext) => emit('editExtension', ext)"
        @edit-post="(p) => emit('editPost', p)"
        @post-updated="handleReload"
        @submit-create-extension="(form, p) => emit('submitCreateExtension', form, p)"
        @submit-create-post="handleSubmitCreatePost"
        @submit-edit="handleSubmitEdit"
        @submit-edit-extension="(form, ext) => emit('submitEditExtension', form, ext)"
        @submit-edit-post="handleSubmitEditPost"
        @toggle-extensions="(p) => emit('toggleExtensions', p)"
        @toggle-posts="(i) => emit('togglePosts', i)"
    >
        <template #badges>
            <div v-if="item.categories && item.categories.length" class="mt-1 flex flex-wrap gap-2">
                <span
                    v-for="cat in item.categories"
                    :key="`badge-${item.id}-${cat.id}`"
                    class="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                >
                    {{ localizedName(cat.name as any) }}
                </span>
            </div>
        </template>

        <template #actions="{ handleEdit, handleCreatePost, handleTogglePosts, isCreatingPost, isEditing, isPostsExpanded }">
            <ItemActionGroup
                :is-creating-post="isCreatingPost"
                :is-editing="isEditing"
                :is-posts-expanded="isPostsExpanded"
                @edit="handleEdit(item)"
                @create-post="handleCreatePost(item)"
                @toggle-posts="handleTogglePosts(item)"
            />
        </template>

        <template #edit-form="{ handleCancelEdit }">
            <BlogForm
                :blog="item"
                :categories="categories"
                :form="props.editForm"
                :id-prefix="`edit-${item.id}`"
                :is-edit="true"
                class="mt-4"
                @cancel="handleCancelEdit"
                @submit="handleSubmitEdit"
            />
        </template>

        <template #create-post-form="{ handleCancelCreatePost }">
            <PostForm
                :blog-id="item.id"
                :form="postForm"
                :id-prefix="`post-${item.id}`"
                :is-edit="false"
                class="mt-4"
                @cancel="handleCancelCreatePost"
                @submit="handleSubmitCreatePost"
            />
        </template>

        <template #no-posts>
            {{ t('blogger.posts.empty') }}
        </template>
    </BloggerListItem>
</template>
