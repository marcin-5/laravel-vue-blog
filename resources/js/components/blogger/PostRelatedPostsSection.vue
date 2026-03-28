<script lang="ts" setup>
import { Button } from '@/components/ui/button/index';
import { Input } from '@/components/ui/input/index';
import { Label } from '@/components/ui/label/index';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select/index';
import type { RelatedPostItem } from '@/types/blog.types';
import { Link, useHttp } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

interface Props {
    items: RelatedPostItem[];
    idPrefix: string;
    translations: {
        label: string;
        addItem: string;
        blogId: string;
        postId: string;
        reason: string;
    };
}

interface Emits {
    (e: 'add-item', item: { blog_id: number; related_post_id: number; reason: string }): void;
    (e: 'remove', index: number): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const availableBlogs = ref<{ id: number; name: string; slug: string }[]>([]);
const availablePosts = ref<{ id: number; title: string }[]>([]);

const selectedBlogId = ref<string>('');
const selectedPostId = ref<string>('');
const selectedReason = ref<string>('');

const isLoadingBlogs = ref(false);
const isLoadingPosts = ref(false);
const http = useHttp();

const fetchBlogs = async () => {
    isLoadingBlogs.value = true;
    try {
        const response = await http.get(route('blogger.data.blogs'));
        availableBlogs.value = response.data;
    } catch (error) {
        console.error('Failed to fetch blogs', error);
    } finally {
        isLoadingBlogs.value = false;
    }
};

const fetchPosts = async (blogId: string) => {
    if (!blogId) {
        availablePosts.value = [];
        return;
    }
    isLoadingPosts.value = true;
    try {
        const response = await http.get(route('blogger.data.posts', { blog: blogId }));
        availablePosts.value = response.data;
    } catch (error) {
        console.error('Failed to fetch posts', error);
    } finally {
        isLoadingPosts.value = false;
    }
};

watch(selectedBlogId, (newBlogId) => {
    selectedPostId.value = '';
    fetchPosts(newBlogId);
});

onMounted(() => {
    fetchBlogs();
});

const handleAddItem = () => {
    if (selectedBlogId.value && selectedPostId.value) {
        emit('add-item', {
            blog_id: parseInt(selectedBlogId.value),
            related_post_id: parseInt(selectedPostId.value),
            reason: selectedReason.value,
        });
        selectedPostId.value = '';
        selectedReason.value = '';
    }
};

const getBlogName = (blogId: number) => {
    return availableBlogs.value.find((b) => b.id === blogId)?.name || `Blog ID: ${blogId}`;
};

const getPostTitle = (rp: RelatedPostItem) => {
    if (rp.related_post?.title) {
        return rp.related_post.title;
    }
    return availablePosts.value.find((p) => p.id === rp.related_post_id)?.title || `Post ID: ${rp.related_post_id}`;
};

const getPostUrl = (rp: RelatedPostItem) => {
    const blog = availableBlogs.value.find((b) => b.id === rp.blog_id);
    const blogSlug = blog?.slug;
    const postSlug = rp.related_post?.slug;

    if (blogSlug && postSlug) {
        return route('blog.public.post', {
            blog: blogSlug,
            postSlug,
        });
    }

    return '#';
};
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">{{ translations.label }}</h3>
        </div>

        <!-- Blog and post selection -->
        <div class="space-y-4 pb-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-select-blog`">{{ translations.blogId }}</Label>
                    <Select :model-value="selectedBlogId" @update:model-value="(v) => (selectedBlogId = v as string)">
                        <SelectTrigger :id="`${idPrefix}-select-blog`">
                            <SelectValue :placeholder="isLoadingBlogs ? 'Loading...' : 'Select blog...'" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="blog in availableBlogs" :key="blog.id" :value="blog.id.toString()">
                                {{ blog.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="space-y-2">
                    <Label :for="`${idPrefix}-select-post`">{{ translations.postId }}</Label>
                    <Select
                        :disabled="!selectedBlogId || isLoadingPosts"
                        :model-value="selectedPostId"
                        @update:model-value="(v) => (selectedPostId = v as string)"
                    >
                        <SelectTrigger :id="`${idPrefix}-select-post`">
                            <SelectValue :placeholder="isLoadingPosts ? 'Loading...' : 'Select post...'" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="post in availablePosts" :key="post.id" :value="post.id.toString()">
                                {{ post.title }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <div class="space-y-2">
                <Label :for="`${idPrefix}-select-reason`">{{ translations.reason }}</Label>
                <Input :id="`${idPrefix}-select-reason`" v-model="selectedReason" />
            </div>
            <div class="flex justify-end">
                <Button :disabled="!selectedBlogId || !selectedPostId" type="button" variant="outline" @click="handleAddItem">
                    <Plus class="mr-2 h-4 w-4" />
                    {{ translations.addItem }}
                </Button>
            </div>
        </div>

        <div v-for="(rp, index) in items" :key="index" class="flex items-start justify-between border-t py-2 first:border-t-0 first:pt-0 last:pb-0">
            <div class="flex-1 space-y-1">
                <Link :href="getPostUrl(rp)" class="text-sm font-medium hover:text-primary-foreground">
                    {{ getBlogName(rp.blog_id) }} - {{ getPostTitle(rp) }}
                </Link>
                <div v-if="rp.reason" class="text-xs text-muted-foreground">
                    {{ rp.reason }}
                </div>
            </div>
            <div class="ml-4 flex items-center">
                <Button
                    class="h-8 w-8 text-destructive hover:bg-destructive/10"
                    size="icon"
                    type="button"
                    variant="ghost"
                    @click="$emit('remove', index)"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
