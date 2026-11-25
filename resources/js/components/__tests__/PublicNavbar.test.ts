import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { describe, expect, it, vi } from 'vitest';
import FontSetToggle from '../FontSetToggle.vue';
import PublicNavbar from '../PublicNavbar.vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: { name: 'Link', template: '<a><slot /></a>' },
    usePage: () => ({ url: '/', props: {} }),
}));

function mountNavbar(hasAlternativeFonts: boolean) {
    return mount(PublicNavbar, {
        props: { hasAlternativeFonts, fontSetActive: false },
        global: {
            plugins: [
                createI18n({
                    legacy: false,
                    messages: {
                        en: {
                            common: {
                                nav: {
                                    about: 'About',
                                    about_home: 'About us',
                                    contact: 'Contact',
                                    login: 'Log in',
                                    register: 'Register',
                                    font_set_toggle: 'Toggle font set',
                                },
                            },
                        },
                    },
                }),
            ],
            stubs: ['CookieConsent', 'FontSizeAdjuster', 'HomeButton', 'ThemeToggle'],
            mocks: {
                route: () => ({ has: () => false }),
            },
        },
    });
}

describe('PublicNavbar.vue font set toggle', () => {
    it('shows the toggle only when an alternative font is available', () => {
        expect(mountNavbar(false).findComponent(FontSetToggle).exists()).toBe(false);
        expect(mountNavbar(true).findComponent(FontSetToggle).exists()).toBe(true);
    });

    it('forwards toggle events from the control', async () => {
        const wrapper = mountNavbar(true);

        await wrapper.findComponent(FontSetToggle).trigger('click');

        expect(wrapper.emitted('toggle-font-set')).toHaveLength(1);
    });
});