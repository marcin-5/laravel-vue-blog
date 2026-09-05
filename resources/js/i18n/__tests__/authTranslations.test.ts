import { createI18n } from 'vue-i18n';
import en from '../../../lang/en/auth.json';
import pl from '../../../lang/pl/auth.json';
import { describe, expect, it } from 'vitest';

describe('authentication translations', () => {
    it.each([
        ['en', en],
        ['pl', pl],
    ] as const)('compiles the registration email placeholder in %s', (locale, messages) => {
        const i18n = createI18n({
            legacy: false,
            locale,
            messages: { [locale]: messages },
        });

        expect(i18n.global.t('auth.register.email_placeholder')).toBe('email@example.com');
    });
});