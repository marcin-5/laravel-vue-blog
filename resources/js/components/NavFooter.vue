<script lang="ts" setup>
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type AppPageProps, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    items: NavItem[];
    class?: string;
}

const props = defineProps<Props>();

const page = usePage<AppPageProps>();

const currentRole = computed(() => page.props.auth.user?.role ?? null);

const visibleItems = computed(() => {
    return props.items.filter((item) => {
        if (!item.roles || item.roles.length === 0) return true;
        if (!currentRole.value) return false;
        return item.roles.includes(currentRole.value);
    });
});
</script>

<template>
    <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in visibleItems" :key="item.title">
                    <SidebarMenuButton as-child class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100">
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
