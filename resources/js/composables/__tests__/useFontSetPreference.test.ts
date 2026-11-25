import { beforeEach, describe, expect, it } from 'vitest';
import { nextTick, ref } from 'vue';
import { useFontSetPreference } from '../useFontSetPreference';

describe('useFontSetPreference', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('stores preferences independently for owner type and ID', async () => {
        const blogPreference = useFontSetPreference('blog', 7);
        const groupPreference = useFontSetPreference('group', 7);

        blogPreference.value = true;
        await nextTick();

        expect(localStorage.getItem('font-set:blog:7')).toBe('true');
        expect(useFontSetPreference('blog', 7).value).toBe(true);
        expect(groupPreference.value).toBe(false);
    });

    it('switches storage when the owner changes', async () => {
        localStorage.setItem('font-set:blog:7', 'true');
        const ownerType = ref<'blog' | 'group'>('blog');
        const ownerId = ref(7);
        const preference = useFontSetPreference(ownerType, ownerId);

        expect(preference.value).toBe(true);

        ownerType.value = 'group';
        await nextTick();

        expect(preference.value).toBe(false);

        localStorage.setItem('font-set:group:8', 'true');
        ownerId.value = 8;
        await nextTick();

        expect(preference.value).toBe(true);
    });
});