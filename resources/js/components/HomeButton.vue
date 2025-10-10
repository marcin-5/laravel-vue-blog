<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { HomeIcon } from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isHomePage = computed(() => page.url === '/');

// Check if current URL is a post page (format: /{blog}/{post})
const isPostPage = computed(() => {
    const urlParts = page.url.split('/').filter(part => part.length > 0);
    return urlParts.length === 2;
});

// Extract blog slug from post URL
const blogSlug = computed(() => {
    if (isPostPage.value) {
        return page.url.split('/').filter(part => part.length > 0)[0];
    }
    return null;
});

// Determine the target URL for the home button
const homeUrl = computed(() => {
    if (isPostPage.value && blogSlug.value) {
        return route('blog.public.landing', { blog: blogSlug.value });
    }
    return route('home');
});

const showButton = computed(() => !isHomePage.value);
</script>

<template>
    <Link v-if="showButton" :href="homeUrl">
        <Button class="flex items-center gap-2" type="button" variant="exit">
            <HomeIcon class="h-4 w-4" />
        </Button>
    </Link>
</template>
