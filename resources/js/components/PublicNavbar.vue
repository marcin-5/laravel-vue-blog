<script lang="ts" setup>
import HomeButton from '@/components/HomeButton.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import CookieConsent from '@/components/CookieConsent.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

// Use translations already injected via props (hydrated in app.ts/ssr.ts)
const { t } = useI18n();
const page = usePage();
const isHomePage = computed(() => page.url === '/');

const user = computed(() => page.props.auth.user);

interface NavLink {
    route: string;
    label: string;
    method?: 'post';
    as?: 'button';
    emphasized?: boolean;
}

const commonLinks = computed<NavLink[]>(() => [
    { route: 'about', label: t('common.nav.about', 'About') },
    { route: 'contact', label: t('common.nav.contact', 'Contact') },
]);

const authLinks = computed<NavLink[]>(() => [
    { route: 'dashboard', label: t('common.nav.dashboard'), emphasized: true },
    { route: 'logout', label: t('common.nav.logout'), method: 'post', as: 'button' },
]);

const guestLinks = computed<NavLink[]>(() => [
    { route: 'login', label: t('common.nav.login') },
    { route: 'register', label: t('common.nav.register'), emphasized: true },
]);

const navLinks = computed<NavLink[]>(() => [...commonLinks.value, ...(user.value ? authLinks.value : guestLinks.value)]);

// Mobile menu state
const mobileOpen = ref(false);
// Close mobile menu on navigation
watch(
    () => page.url,
    () => {
        mobileOpen.value = false;
    },
);
</script>
<template>
    <header class="w-full px-4 pt-4 text-sm sm:px-6 lg:px-8">
        <nav :class="['mx-auto flex w-full max-w-[1024px] items-center gap-4', isHomePage ? 'justify-end' : 'justify-between']">
            <HomeButton class="pl-4" />
            <div class="flex items-center gap-3">
                <!-- Desktop navigation -->
                <div class="hidden items-center gap-4 md:flex">
                    <Link
                        v-for="link in navLinks"
                        :key="link.route"
                        :as="link.as"
                        :class="[
                            'inline-block rounded-sm px-5 py-1.5 text-sm leading-normal text-[#1b1b18] dark:text-[#EDEDEC]',
                            link.emphasized
                                ? 'border border-[#19140035] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]'
                                : 'border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A]',
                        ]"
                        :href="route(link.route)"
                        :method="link.method"
                    >
                        {{ link.label }}
                    </Link>
                </div>
                <!-- Theme toggle is always visible -->
                <ThemeToggle />
                <!-- Hamburger (mobile only), placed next to ThemeToggle) -->
                <button
                    :aria-expanded="mobileOpen"
                    aria-controls="mobile-menu"
                    aria-label="Toggle menu"
                    class="inline-flex items-center justify-center rounded-sm border border-transparent p-2 text-[#1b1b18] hover:border-[#19140035] md:hidden dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    type="button"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Menu v-if="!mobileOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>
        </nav>
        <!-- Mobile menu panel -->
        <div v-if="mobileOpen" id="mobile-menu" class="md:hidden">
            <div class="mx-auto w-full max-w-[1024px] px-4 pt-3 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 border-t border-[#19140035] pt-3 dark:border-[#3E3E3A]">
                    <Link
                        v-for="link in navLinks"
                        :key="link.route"
                        :as="link.as"
                        :class="[
                            'inline-block rounded-sm px-4 py-2 text-sm leading-normal text-[#1b1b18] dark:text-[#EDEDEC]',
                            link.emphasized
                                ? 'border border-[#19140035] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]'
                                : 'border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A]',
                        ]"
                        :href="route(link.route)"
                        :method="link.method"
                    >
                        {{ link.label }}
                    </Link>
                </div>
            </div>
        </div>
        <CookieConsent />
    </header>
</template>
