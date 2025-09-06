<script lang="ts" setup>
import ThemeToggle from '@/components/ThemeToggle.vue';
import { ensureNamespace } from '@/i18n';
import { Head, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n';
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
  pagination?: { links: { url: string | null; label: string; active: boolean }[] } | null
}>()

const { t, locale } = useI18n();
// Ensure 'landing' namespace is available for nav labels (SSR-safe with Suspense)
await ensureNamespace(locale.value, 'landing');
</script>

<template>
  <Head :title="`${post.title} - ${blog.name}`">
    <meta :content="post.title" name="description" />
  </Head>

  <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
    <header class="w-full px-4 pt-4 text-sm sm:px-6 lg:px-8">
      <nav class="mx-auto flex w-full max-w-[1024px] items-center justify-end gap-4">
        <template v-if="$page.props.auth.user">
          <Link
            :href="route('dashboard')"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
          >
            {{ t('landing.nav.dashboard') }}
          </Link>
          <Link
            :href="route('logout')"
            as="button"
            class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
            method="post"
          >
            {{ t('landing.nav.logout') }}
          </Link>
        </template>
        <template v-else>
          <Link
            :href="route('login')"
            class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
          >
            {{ t('landing.nav.login') }}
          </Link>
          <Link
            :href="route('register')"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
          >
            {{ t('landing.nav.register') }}
          </Link>
        </template>
        <ThemeToggle />
      </nav>
    </header>

    <div class="mx-auto w-full max-w-[1024px] p-4">
    <header class="mb-4">
      <h1 class="text-2xl font-bold">{{ post.title }}</h1>
      <p v-if="post.published_at" class="text-gray-600">Published {{ post.published_at }}</p>
    </header>

      <div v-if="sidebarPosition === 'left'" class="flex items-start gap-8">
        <aside class="w-[280px]">
          <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" />
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
          <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" />
        </aside>
      </div>

      <div v-else>
        <main class="min-w-0 flex-1">
          <article class="prose max-w-none" v-html="post.contentHtml" />
        </main>
        <BlogPostsList :blogSlug="blog.slug" :posts="posts" :pagination="pagination" class="mt-6" />
      </div>
    </div>
  </div>
</template>
