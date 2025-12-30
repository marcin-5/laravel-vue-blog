<script lang="ts" setup>
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { useI18nNs } from '@/composables/useI18nNs';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';

interface Props {
    user: User;
}

defineProps<Props>();

const { t } = await useI18nNs(['common']);
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :show-email="true" :user="user" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link :href="route('profile.edit')" class="flex w-full items-center" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                {{ t('common.nav.settings') }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link :href="route('logout')" as="button" class="flex w-full items-center" method="post">
            <LogOut class="mr-2 h-4 w-4" />
            {{ t('common.nav.logout') }}
        </Link>
    </DropdownMenuItem>
</template>
