import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';

const { pageState, translateMock } = vi.hoisted(() => ({
    pageState: { url: '/' },
    translateMock: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => pageState,
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        tm: translateMock,
    }),
}));

import { getStableSloganIndex, normalizeSlogans, useWelcomeSlogan } from '../useWelcomeSlogan';

describe('useWelcomeSlogan', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    beforeEach(() => {
        pageState.url = '/';
        translateMock.mockReset();
    });

    it('normalizes only string slogan collections', () => {
        expect(normalizeSlogans(['First', 2, null, 'Second'])).toEqual(['First', 'Second']);
        expect(normalizeSlogans('not a collection')).toEqual([]);
    });

    it('returns an empty slogan when translations are unavailable', () => {
        translateMock.mockReturnValue(undefined);
        const wrapper = mount(
            defineComponent({
                setup() {
                    const { slogan } = useWelcomeSlogan();

                    return () => h('span', slogan.value);
                },
            }),
        );

        expect(wrapper.text()).toBe('');
        wrapper.unmount();
    });

    it('selects the same translated slogan for repeated evaluation of one URL', () => {
        const slogans = ['First', 'Second', 'Third'];
        translateMock.mockReturnValue(slogans);
        pageState.url = '/?categories=2,4';

        const firstWrapper = mount(
            defineComponent({
                setup() {
                    const { slogan } = useWelcomeSlogan();

                    return () => h('span', slogan.value);
                },
            }),
        );
        const secondWrapper = mount(
            defineComponent({
                setup() {
                    const { slogan } = useWelcomeSlogan();

                    return () => h('span', slogan.value);
                },
            }),
        );

        expect(firstWrapper.text()).toBe(secondWrapper.text());
        expect(firstWrapper.text()).toBe(slogans[getStableSloganIndex(pageState.url, slogans.length)]);

        firstWrapper.unmount();
        secondWrapper.unmount();
    });

    it('changes predictably when the URL changes', () => {
        const slogans = ['First', 'Second', 'Third'];
        translateMock.mockReturnValue(slogans);
        pageState.url = '/alpha';
        const firstWrapper = mount(
            defineComponent({
                setup() {
                    const { slogan } = useWelcomeSlogan();

                    return () => h('span', slogan.value);
                },
            }),
        );

        pageState.url = '/beta';
        const secondWrapper = mount(
            defineComponent({
                setup() {
                    const { slogan } = useWelcomeSlogan();

                    return () => h('span', slogan.value);
                },
            }),
        );

        expect(firstWrapper.text()).toBe(slogans[getStableSloganIndex('/alpha', slogans.length)]);
        expect(secondWrapper.text()).toBe(slogans[getStableSloganIndex('/beta', slogans.length)]);
        expect(firstWrapper.text()).not.toBe(secondWrapper.text());

        firstWrapper.unmount();
        secondWrapper.unmount();
    });

    it('returns -1 when there are no slogans to select', () => {
        expect(getStableSloganIndex('/anywhere', 0)).toBe(-1);
    });
});
