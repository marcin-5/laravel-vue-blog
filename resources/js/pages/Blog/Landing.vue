<script lang="ts" setup>
import { Head } from '@inertiajs/vue3';
import BlogPostsList from '../../components/BlogPostsList.vue';

interface Blog {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
}

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    published_at?: string | null;
}

const props = defineProps<{
    blog: Blog;
    landingHtml: string;
    posts: PostItem[];
    sidebarPosition: 'left' | 'right' | 'none';
}>();

const hasLanding = !!props.landingHtml;
</script>

<template>
    <Head :title="blog.name">
        <meta :content="blog.description || blog.name" name="description" />
    </Head>

    <div class="mx-auto max-w-[1024px] p-4">
        <header class="mb-4">
            <h1 class="text-2xl font-bold">{{ blog.name }}</h1>
            <p v-if="blog.description" class="text-gray-600">{{ blog.description }}</p>
        </header>

        <template v-if="hasLanding">
            <div v-if="sidebarPosition === 'left'" class="flex items-start gap-8">
                <aside class="w-[280px]">
                    <BlogPostsList :blogSlug="blog.slug" :posts="posts" />
                </aside>
                <main class="min-w-0 flex-1">
                    <div class="prose max-w-none" v-html="landingHtml" />
                </main>
            </div>

            <div v-else-if="sidebarPosition === 'right'" class="flex items-start gap-8">
                <main class="min-w-0 flex-1">
                    <div class="prose max-w-none" v-html="landingHtml" />
                </main>
                <aside class="w-[280px]">
                    <BlogPostsList :blogSlug="blog.slug" :posts="posts" />
                </aside>
            </div>

            <div v-else>
                <main class="min-w-0 flex-1">
                    <div class="prose max-w-none" v-html="landingHtml" />
                </main>
                <BlogPostsList :blogSlug="blog.slug" :posts="posts" class="mt-6" />
            </div>
        </template>

        <template v-else>
            <BlogPostsList :blogSlug="blog.slug" :posts="posts" />
        </template>
    </div>
</template>
