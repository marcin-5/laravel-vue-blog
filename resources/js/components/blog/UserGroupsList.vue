<script lang="ts" setup>
import type { AppPageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage<AppPageProps>();
const userGroups = computed(() => page.props.userGroups ?? []);
</script>

<template>
    <div
        v-if="userGroups.length"
        class="border-border-100/50 mb-12 rounded-3xl border border-gray-200 bg-olive-100 p-4 shadow-md backdrop-blur-sm hover:shadow-sm dark:border-gray-800 dark:border-zinc-700/50 dark:bg-mist-900 dark:from-zinc-900/50 dark:to-zinc-800/50"
    >
        <h2 class="mb-5 text-center text-2xl font-semibold text-mist-800 text-primary drop-shadow-lg dark:text-mist-100">
            {{ t('welcome.my_groups') }}
        </h2>
        <div class="grid grid-cols-1 gap-5">
            <Link
                v-for="group in userGroups"
                :key="group.id"
                :href="route('group.landing', group.slug)"
                class="group-card group flex flex-col rounded-2xl border-gray-300 bg-stone-50 px-4 py-2 text-gray-700 shadow-md backdrop-blur-md transition-all duration-300 hover:-translate-y-2 hover:shadow-lg dark:border-gray-600 dark:bg-mist-800 dark:text-zinc-200"
            >
                <h3 class="font-sans text-xl">
                    {{ group.name }}
                </h3>
            </Link>
        </div>
    </div>
</template>
