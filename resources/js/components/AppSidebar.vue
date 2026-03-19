<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BarChart3, BookOpen, Folder, LayoutGrid, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('common.nav.dashboard'),
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: t('common.nav.users'),
        href: '/admin/users',
        icon: Users,
        roles: ['view_admin_users'],
    },
    {
        title: t('common.nav.categories'),
        href: '/admin/categories',
        icon: Folder,
        roles: ['view_admin_categories'],
    },
    {
        title: t('common.nav.statistics'),
        href: '/admin/stats',
        icon: BarChart3,
        roles: ['view_admin_stats'],
    },
    {
        title: t('common.nav.blogs'),
        href: '/blogs',
        icon: BookOpen,
        roles: ['view_blogs'],
    },
    {
        title: t('common.nav.statistics'),
        href: '/blogs/stats',
        icon: BarChart3,
        roles: ['view_blogger_stats'],
    },
]);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('blogger.groups.title'),
        href: '/groups/content',
        icon: BookOpen,
        roles: ['contribute_groups'],
    },
    {
        title: t('blogger.groups.members.title'),
        href: '/groups/members',
        icon: Users,
        roles: ['manage_groups'],
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <Link :href="route('home')" class="flex justify-center py-4 text-center">
                        <AppLogo class="group-data-[collapsible=icon]:hidden" size="md" />
                    </Link>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
