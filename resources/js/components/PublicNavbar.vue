<script lang="ts" setup>
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
import { onMounted, onServerPrefetch } from 'vue';
import { useI18n } from 'vue-i18n';
import { ensureNamespace } from '@/i18n';

const composer = useI18n();
const { t, locale } = composer;

// Ensure the 'landing' namespace is available on both SSR and client to prevent key flash
const loadNs = async () => {
    try {
        await ensureNamespace(locale.value, 'landing', composer);
    } catch (e) {
        console.warn('Failed to load landing namespace in PublicNavbar:', e);
    }
};

onServerPrefetch(loadNs);
onMounted(loadNs);
</script>

<template>
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
</template>
