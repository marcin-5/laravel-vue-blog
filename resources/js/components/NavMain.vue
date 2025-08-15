<script lang="ts" setup>
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type AppPageProps, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ items: NavItem[] }>();

const page = usePage<AppPageProps>();

const currentRole = computed(() => page.props.auth.user?.role ?? null);

const visibleItems = computed(() => {
    return props.items.filter((item) => {
        if (!item.roles || item.roles.length === 0) return true; // visible to all if roles not provided
        if (!currentRole.value) return false; // not logged in and item requires a role
        return item.roles.includes(currentRole.value);
    });
});
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in visibleItems" :key="item.title">
                <SidebarMenuButton :is-active="item.href === page.url" :tooltip="item.title" as-child>
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
