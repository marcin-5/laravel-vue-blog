<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Users } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        // No roles => visible to everyone
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
        roles: ['admin'], // only admins see this
    },
    {
        title: 'Categories',
        href: '/admin/categories',
        icon: Folder,
        roles: ['admin'], // only admins see this
    },
    {
        title: 'Blogs',
        href: '/blogs',
        icon: BookOpen,
        roles: ['admin', 'blogger'],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'footerNavItem',
        href: '/dashboard',
        icon: Folder,
    },
    {
        title: 'footerNavItem',
        href: '/dashboard',
        icon: BookOpen,
    },
];
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
    <slot />
</template>
