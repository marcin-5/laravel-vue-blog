<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { HomeIcon } from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isHomePage = computed(() => page.url === '/' && !(page.props as any).currentBlogSlug);

const currentBlogSlug = computed(() => (page.props as any).currentBlogSlug);
const mainDomain = computed(() => (page.props as any).mainDomain);

// Determine the target URL for the home button
const homeUrl = computed(() => {
    if (currentBlogSlug.value) {
        return route('blog.public.landing', { blog: currentBlogSlug.value, mainDomain: mainDomain.value });
    }
    return route('home');
});

const showButton = computed(() => {
    // Show if not on main home page
    if (!currentBlogSlug.value) {
        return !isHomePage.value;
    }
    // On subdomain, show if not on blog landing
    return page.url !== '/';
});
</script>

<template>
    <Link v-if="showButton" :href="homeUrl">
        <Button class="flex items-center gap-2" type="button" variant="exit">
            <HomeIcon class="h-4 w-4" />
        </Button>
    </Link>
</template>
