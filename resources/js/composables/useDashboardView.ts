import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

export type DashboardView = 'admin' | 'blogger' | 'user';

const currentView = ref<DashboardView | null>(null);

export function useDashboardView() {
    const page = usePage<AppPageProps>();
    const user = computed(() => page.props.auth.user);

    const availableViews = computed(() => {
        if (!user.value) return [];
        if (user.value.role === 'admin') return ['admin', 'blogger', 'user'] as DashboardView[];
        if (user.value.role === 'blogger') return ['blogger', 'user'] as DashboardView[];
        return [];
    });

    // Initialize default view if not set
    if (!currentView.value && user.value) {
        if (user.value.role === 'admin') currentView.value = 'admin';
        else if (user.value.role === 'blogger') currentView.value = 'blogger';
        else currentView.value = 'user';
    }

    const setView = (view: DashboardView) => {
        if (availableViews.value.includes(view) || view === 'user') {
            currentView.value = view;
        }
    };

    return {
        currentView,
        availableViews,
        setView,
    };
}
