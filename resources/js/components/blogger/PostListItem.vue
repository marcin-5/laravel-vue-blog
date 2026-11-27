<script lang="ts" setup>
import PostForm from '@/components/blogger/PostForm.vue';
import { Badge } from '@/components/ui/badge';
import { TooltipButton } from '@/components/ui/tooltip';
import { useToast } from '@/composables/useToast';
import type { AdminPostItem as PostItem } from '@/types/blog.types';
import { router, usePage } from '@inertiajs/vue3';
import { Copy, Link as LinkIcon, Pencil, X } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { toast } = useToast();
const page = usePage();

interface Props {
    post: PostItem;
    isEditing: boolean;
    editForm?: any; // External edit form instance
    sourceContext: 'blog' | 'group';
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

const extensionsUrl = computed(() => {
    const parameters = props.sourceContext === 'group' ? { post: props.post.id, context: 'group' } : { post: props.post.id };

    return route('blogger.posts.extensions.index', parameters);
});

const postUrl = computed(() => {
    // We try to find the blog slug from the page props or from the post object if it was loaded with it
    const blog = (page.props.blogs as any[])?.find((b: any) => b.id === props.post.blog_id);
    const blogSlug = blog?.slug;
    const mainDomain = blog?.main_domain;

    if (!blogSlug || !mainDomain) {
        return '';
    }

    return route('blog.public.post', {
        blog: blogSlug,
        mainDomain,
        postSlug: props.post.slug,
    });
});

function copyUrl() {
    if (!postUrl.value) {
        return;
    }

    navigator.clipboard.writeText(postUrl.value).then(() => {
        toast({
            title: t('blogger.post_item.url_copied'),
            variant: 'success',
        });
    });
}
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
                <TooltipButton
                    v-if="post.visibility === 'unlisted'"
                    :tooltip-content="t('blogger.post_item.copy_url_button')"
                    size="icon"
                    variant="ghost"
                    @click="copyUrl"
                >
                    <Copy />
                </TooltipButton>

                <TooltipButton :tooltip-content="editButtonLabel" :variant="editButtonVariant" size="icon" @click="emit('edit', post)">
                    <X v-if="isEditing" />
                    <Pencil v-else />
                </TooltipButton>

                <TooltipButton
                    v-if="post.visibility !== 'extension'"
                    :tooltip-content="t('blogger.post_item.manage_extensions')"
                    data-testid="manage-extensions"
                    size="icon"
                    variant="ghost"
                    @click="router.visit(extensionsUrl)"
                >
                    <LinkIcon />
                </TooltipButton>
            </div>
        </div>
        <!-- Inline Post Edit Form -->
        <PostForm
            v-if="isEditing"
            :form="editForm"
            :group-id="post.group_id ?? undefined"
            :id-prefix="`edit-post-${post.id}`"
            :is-edit="true"
            :post="post"
            @cancel="emit('cancelEdit')"
            @submit="(form) => emit('submitEdit', form, post)"
        />
    </div>
</template>
