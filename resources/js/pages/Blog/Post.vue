<script lang="ts" setup>
import { Head } from '@inertiajs/vue3'
import BlogPostsList from '../../components/BlogPostsList.vue'

interface Blog {
  id: number
  name: string
  slug: string
  description?: string | null
}

interface PostDetails {
  id: number
  title: string
  slug: string
  contentHtml: string
  published_at?: string | null
}

interface PostItem {
  id: number
  title: string
  slug: string
  excerpt?: string | null
  published_at?: string | null
}

defineProps<{
  blog: Blog
  post: PostDetails
  posts: PostItem[]
  sidebarPosition: 'left' | 'right' | 'none'
}>()
</script>

<template>
  <Head :title="`${post.title} - ${blog.name}`">
    <meta :content="post.title" name="description" />
  </Head>

  <div class="mx-auto max-w-[1024px] p-4">
    <header class="mb-4">
      <h1 class="text-2xl font-bold">{{ post.title }}</h1>
      <p v-if="post.published_at" class="text-gray-600">Published {{ post.published_at }}</p>
    </header>

    <div v-if="sidebarPosition === 'left'" class="flex items-start gap-8">
      <aside class="w-[280px]">
        <BlogPostsList :blogSlug="blog.slug" :posts="posts" />
      </aside>
      <main class="min-w-0 flex-1">
        <article class="prose max-w-none" v-html="post.contentHtml" />
      </main>
    </div>

    <div v-else-if="sidebarPosition === 'right'" class="flex items-start gap-8">
      <main class="min-w-0 flex-1">
        <article class="prose max-w-none" v-html="post.contentHtml" />
      </main>
      <aside class="w-[280px]">
        <BlogPostsList :blogSlug="blog.slug" :posts="posts" />
      </aside>
    </div>

    <div v-else>
      <main class="min-w-0 flex-1">
        <article class="prose max-w-none" v-html="post.contentHtml" />
      </main>
      <BlogPostsList :blogSlug="blog.slug" :posts="posts" class="mt-6" />
    </div>
  </div>
</template>
