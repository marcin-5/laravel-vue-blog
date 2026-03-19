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
        const views: DashboardView[] = ['user'];
        if (user.value.can?.view_admin_stats) views.unshift('admin');
        if (user.value.can?.view_blogger_stats) views.unshift('blogger');
        return [...new Set(views)];
    });

    // Initialize default view if not set
    if (!currentView.value && user.value) {
        if (user.value.can?.view_admin_stats) currentView.value = 'admin';
        else if (user.value.can?.view_blogger_stats) currentView.value = 'blogger';
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
