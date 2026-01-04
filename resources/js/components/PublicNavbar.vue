<script lang="ts" setup>
import CookieConsent from '@/components/CookieConsent.vue';
import HomeButton from '@/components/HomeButton.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        maxWidth?: string;
    }>(),
    {
        maxWidth: 'max-w-[1024px]',
    },
);

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
    prefetch?: boolean;
}

const commonLinks = computed<NavLink[]>(() => [
    { route: 'about', label: t('common.nav.about', 'About') },
    { route: 'contact', label: t('common.nav.contact', 'Contact') },
]);

const authLinks = computed<NavLink[]>(() => [
    { route: 'dashboard', label: t('common.nav.dashboard'), emphasized: true, prefetch: true },
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
    <header class="w-full pt-4 text-sm">
        <nav :class="['mx-auto flex w-full items-center gap-4 sm:px-12 md:px-16', props.maxWidth, isHomePage ? 'justify-end' : 'justify-between']">
            <HomeButton class="pl-4" />
            <div class="flex items-center gap-3">
                <!-- Desktop navigation -->
                <div class="hidden items-center gap-4 md:flex">
                    <Link
                        v-for="link in navLinks"
                        :key="link.route"
                        :as="link.as"
                        :class="[
                            'inline-block rounded-sm px-5 py-1.5 text-sm leading-normal text-primary',
                            link.emphasized
                                ? 'border border-border hover:border-primary/20 dark:hover:border-primary/30'
                                : 'border border-transparent hover:border-border',
                        ]"
                        :href="route(link.route)"
                        :method="link.method"
                        :prefetch="link.prefetch"
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
                    class="inline-flex items-center justify-center rounded-sm border border-transparent p-2 text-foreground hover:border-border md:hidden"
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
            <div :class="['mx-auto w-full px-4 pt-3 sm:px-6 lg:px-8', props.maxWidth]">
                <div class="flex flex-col gap-2 border-t border-border pt-3">
                    <Link
                        v-for="link in navLinks"
                        :key="link.route"
                        :as="link.as"
                        :class="[
                            'inline-block rounded-sm px-4 py-2 text-sm leading-normal text-primary',
                            link.emphasized
                                ? 'border border-border hover:border-primary/20 dark:hover:border-primary/30'
                                : 'border border-transparent hover:border-border',
                        ]"
                        :href="route(link.route)"
                        :method="link.method"
                        :prefetch="link.prefetch"
                    >
                        {{ link.label }}
                    </Link>
                </div>
            </div>
        </div>
        <CookieConsent />
    </header>
</template>
