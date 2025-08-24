<script lang="ts" setup>
import { Link } from '@inertiajs/vue3'

interface PostItem {
  id: number
  title: string
  slug: string
  excerpt?: string | null
  published_at?: string | null
}

defineProps<{
  posts: PostItem[]
  blogSlug: string
}>()
</script>

<template>
  <section aria-label="Posts list">
    <h2 class="mb-2 text-xl font-semibold">Posts</h2>
    <p v-if="!posts || posts.length === 0">No posts yet.</p>
    <ul v-else class="space-y-3">
      <li v-for="p in posts" :key="p.id">
        <Link :href="route('blog.public.post', { blog: blogSlug, postSlug: p.slug })" class="text-blue-600 hover:underline">
          {{ p.title }}
        </Link>
        <small v-if="p.published_at" class="text-gray-500"> · {{ p.published_at }}</small>
        <div v-if="p.excerpt" class="text-gray-700">{{ p.excerpt }}</div>
      </li>
    </ul>
  </section>
</template>
