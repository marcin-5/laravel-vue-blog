import type { Ref } from 'vue';
import type { Role, UserWithQuota } from '@/types/admin.types';

export type { Role, UserWithQuota } from '@/types/admin.types';

export interface UseUserPermissionsOptions {
    currentUserIsAdmin?: boolean;
    originalsById?: Ref<Map<number, UserWithQuota>>;
}

export function useUserPermissions(options: UseUserPermissionsOptions) {
    const { currentUserIsAdmin, originalsById } = options;

    function canEditQuota(user: UserWithQuota): boolean {
        if (!currentUserIsAdmin) {
            return false;
        }

        if (!originalsById) {
            // For new users (no original), allow editing quota if role allows it
            return user.role === 'blogger' || user.role === 'admin';
        }

        const original = originalsById.value.get(user.id);

        return !!original && (original.role === 'blogger' || original.role === 'admin');
    }

    function canEditQuotaByRole(role: Role): boolean {
        if (!currentUserIsAdmin) {
            return false;
        }

        return role === 'blogger' || role === 'admin';
    }

    return { canEditQuota, canEditQuotaByRole };
}
