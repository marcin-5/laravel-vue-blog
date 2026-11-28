import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import IndexNowFailures from '../IndexNowFailures.vue';

const mocks = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useHttp: () => ({
        get: mocks.get,
        post: mocks.post,
    }),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string, values?: Record<string, unknown>) => (values ? `${key}:${values.count}` : key),
    }),
}));

const failure = {
    uuid: 'b048f9de-a82d-40d4-a336-bdbe51ddadc2',
    failed_at: '2026-11-27T23:20:43.000000Z',
    exception: 'Permission denied while creating the IndexNow key file.',
};
const longException = 'IndexNowSubmitJob stack frame\n'.repeat(1000);
const originalClipboard = navigator.clipboard;

describe('IndexNowFailures.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.stubGlobal('route', (name: string, parameters?: { uuid: string }) => {
            if (name === 'admin.logs.indexnow-failures.retry') {
                return `/admin/logs/indexnow-failures/${parameters?.uuid}/retry`;
            }

            return '/admin/logs/indexnow-failures';
        });
    });

    afterEach(() => {
        if (originalClipboard) {
            Object.defineProperty(navigator, 'clipboard', { configurable: true, value: originalClipboard });
        } else {
            Reflect.deleteProperty(navigator, 'clipboard');
        }

        vi.unstubAllGlobals();
    });

    it('does not render a failure signal or fetch details when there are no failed jobs', () => {
        const wrapper = mount(IndexNowFailures, { props: { failureCount: 0 } });

        expect(wrapper.find('[data-testid="indexnow-failure-signal"]').exists()).toBe(false);
        expect(mocks.get).not.toHaveBeenCalled();
    });

    it('loads details only after the administrator expands the failure signal', async () => {
        let resolveRequest!: (response: { failures: (typeof failure)[] }) => void;
        mocks.get.mockReturnValue(new Promise((resolve) => (resolveRequest = resolve)));

        const wrapper = mount(IndexNowFailures, { props: { failureCount: 1 } });

        expect(mocks.get).not.toHaveBeenCalled();
        await wrapper.get('[data-testid="indexnow-failures-toggle"]').trigger('click');

        expect(mocks.get).toHaveBeenCalledWith('/admin/logs/indexnow-failures');
        expect(wrapper.text()).toContain('admin.logs.indexnow_loading');

        resolveRequest({ failures: [failure] });
        await flushPromises();

        expect(wrapper.text()).toContain(failure.exception);
    });

    it('shows an error when failure details cannot be loaded', async () => {
        mocks.get.mockRejectedValue(new Error('Request failed'));

        const wrapper = mount(IndexNowFailures, { props: { failureCount: 1 } });
        await wrapper.get('[data-testid="indexnow-failures-toggle"]').trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('admin.logs.indexnow_load_failed');
    });

    it('retries only the selected failed job and removes it from the displayed list', async () => {
        mocks.get.mockResolvedValue({ failures: [failure] });
        mocks.post.mockResolvedValue({ retried: true });

        const wrapper = mount(IndexNowFailures, { props: { failureCount: 2 } });
        await wrapper.get('[data-testid="indexnow-failures-toggle"]').trigger('click');
        await flushPromises();
        await wrapper.get(`[data-testid="indexnow-retry-${failure.uuid}"]`).trigger('click');
        await flushPromises();

        expect(mocks.post).toHaveBeenCalledWith(`/admin/logs/indexnow-failures/${failure.uuid}/retry`);
        expect(wrapper.text()).not.toContain(failure.exception);
        expect(wrapper.get('[data-testid="indexnow-failure-count"]').text()).toContain(':1');
    });

    it('keeps the failed job visible and reports an error when retry fails', async () => {
        mocks.get.mockResolvedValue({ failures: [failure] });
        mocks.post.mockRejectedValue(new Error('Retry failed'));

        const wrapper = mount(IndexNowFailures, { props: { failureCount: 1 } });
        await wrapper.get('[data-testid="indexnow-failures-toggle"]').trigger('click');
        await flushPromises();
        await wrapper.get(`[data-testid="indexnow-retry-${failure.uuid}"]`).trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('admin.logs.indexnow_retry_failed');
        expect(wrapper.text()).toContain(failure.exception);
    });

    it('copies the stack trace and constrains its display height', async () => {
        const clipboardWriteText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: clipboardWriteText } });
        mocks.get.mockResolvedValue({ failures: [{ ...failure, exception: longException }] });

        const wrapper = mount(IndexNowFailures, { props: { failureCount: 1 } });
        await wrapper.get('[data-testid="indexnow-failures-toggle"]').trigger('click');
        await flushPromises();
        await wrapper.get(`[data-testid="indexnow-copy-${failure.uuid}"]`).trigger('click');

        expect(clipboardWriteText).toHaveBeenCalledWith(longException);
        expect(wrapper.text()).toContain('admin.logs.indexnow_copied');
        const exception = wrapper.get(`[data-testid="indexnow-exception-${failure.uuid}"]`);
        expect(exception.text().trimEnd()).toBe(longException.trimEnd());
        expect(exception.classes()).toContain('max-h-64');
        expect(exception.classes()).toContain('overflow-auto');
    });
});
