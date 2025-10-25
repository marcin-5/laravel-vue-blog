<script lang="ts" setup>
import HomeButton from '@/components/HomeButton.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

// Use translations already injected via props (hydrated in app.ts/ssr.ts)
const { t } = useI18n();

const page = usePage();
const isHomePage = computed(() => page.url === '/');
</script>

<template>
    <header class="w-full px-4 pt-4 text-sm sm:px-6 lg:px-8">
        <nav :class="['mx-auto flex w-full max-w-[1024px] items-center gap-4', isHomePage ? 'justify-end' : 'justify-between']">
            <HomeButton class="pl-4" />
            <div class="flex items-center gap-4">
                <Link
                    :href="route('about')"
                    class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                >
                    {{ t('common.nav.about', 'About') }}
                </Link>
                <Link
                    :href="route('contact')"
                    class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                >
                    {{ t('common.nav.contact', 'Contact') }}
                </Link>
                <template v-if="$page.props.auth.user">
                    <Link
                        :href="route('dashboard')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        {{ t('common.nav.dashboard') }}
                    </Link>
                    <Link
                        :href="route('logout')"
                        as="button"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        method="post"
                    >
                        {{ t('common.nav.logout') }}
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        {{ t('common.nav.login') }}
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        {{ t('common.nav.register') }}
                    </Link>
                </template>
                <ThemeToggle />
            </div>
        </nav>
    </header>
</template>
