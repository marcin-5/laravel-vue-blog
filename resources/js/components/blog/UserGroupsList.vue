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
        class="border-border-100/50 mb-12 rounded-3xl border border-gray-200 bg-olive-100 p-4 p-8 shadow-md backdrop-blur-sm hover:shadow-sm dark:border-gray-800 dark:border-slate-700/50 dark:bg-slate-900 dark:from-slate-900/50 dark:to-slate-800/50"
    >
        <h2 class="mb-10 text-center text-2xl font-bold text-mist-800 text-primary drop-shadow-lg md:text-3xl dark:text-mist-100">
            {{ t('welcome.my_groups') }}
        </h2>
        <div class="grid grid-cols-1 gap-5">
            <Link
                v-for="group in userGroups"
                :key="group.id"
                :href="route('group.landing', group.slug)"
                class="group-card group flex flex-col rounded-2xl border-gray-300 bg-mist-50 p-6 text-gray-700 shadow-md backdrop-blur-md transition-all duration-300 hover:-translate-y-2 hover:shadow-lg dark:border-gray-600 dark:bg-slate-800 dark:text-slate-200"
            >
                <h3 class="-my-2 text-xl font-bold">
                    {{ group.name }}
                </h3>
            </Link>
        </div>
    </div>
</template>
