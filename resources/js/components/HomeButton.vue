<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { HomeIcon } from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isHomePage = computed(() => page.url === '/');
</script>

<template>
    <Link v-if="!isHomePage" :href="route('home')">
        <Button class="flex items-center gap-2" type="button" variant="exit">
            <HomeIcon class="h-4 w-4" />
        </Button>
    </Link>
</template>
