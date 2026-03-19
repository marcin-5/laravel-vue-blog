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

const visibleItems = computed(() => {
    return props.items.filter((item) => {
        if (!item.roles || item.roles.length === 0) return true;
        const userPermissions = page.props.auth.user?.can ?? {};
        return item.roles.some((permission) => userPermissions[permission] === true);
    });
});
</script>

<template>
    <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in visibleItems" :key="item.title">
                    <SidebarMenuButton
                        :class="item.href === page.url ? 'cursor-default' : 'cursor-pointer'"
                        :is-active="item.href === page.url"
                        as-child
                    >
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
