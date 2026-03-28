import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useMarkdownPreview } from '../useMarkdownPreview';

// Mock Inertia useHttp hook
const postMock = vi.fn();
vi.mock('@inertiajs/vue3', () => ({
    useHttp: () => ({
        post: postMock,
    }),
}));

(global as any).route = vi.fn(() => 'mock-route');

describe('useMarkdownPreview', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        postMock.mockReset();
    });

    it('initializes with default values', () => {
        const { isPreviewMode, isFullPreview, previewLayout, previewHtml } = useMarkdownPreview();

        expect(isPreviewMode.value).toBe(false);
        expect(isFullPreview.value).toBe(false);
        expect(previewLayout.value).toBe('vertical');
        expect(previewHtml.value).toBe('');
    });

    it('setLayout updates previewLayout', () => {
        const { previewLayout, setLayout } = useMarkdownPreview();

        setLayout('horizontal');
        expect(previewLayout.value).toBe('horizontal');

        setLayout('vertical');
        expect(previewLayout.value).toBe('vertical');
    });

    it('renderMarkdown sets previewHtml to empty string when content is empty', async () => {
        const { previewHtml, renderMarkdown } = useMarkdownPreview();

        previewHtml.value = 'some previous html';
        await renderMarkdown('');

        expect(previewHtml.value).toBe('');
    });

    it('renderMarkdown fetches and sets previewHtml on success', async () => {
        postMock.mockResolvedValueOnce({ data: { html: '<p>Hello</p>' } });

        const { previewHtml, renderMarkdown } = useMarkdownPreview();
        await renderMarkdown('# Hello');

        expect(previewHtml.value).toBe('<p>Hello</p>');
    });

    it('renderMarkdown sets error html on fetch failure', async () => {
        postMock.mockRejectedValueOnce(new Error('Network error'));

        const { previewHtml, renderMarkdown } = useMarkdownPreview();
        await renderMarkdown('# Hello');

        expect(previewHtml.value).toBe('<p class="text-error">Error rendering markdown</p>');
    });

    it('togglePreview toggles isPreviewMode and renders markdown when enabled', async () => {
        postMock.mockResolvedValueOnce({ data: { html: '<p>Content</p>' } });

        const { isPreviewMode, previewHtml, togglePreview } = useMarkdownPreview();

        expect(isPreviewMode.value).toBe(false);
        await togglePreview('# Content');

        expect(isPreviewMode.value).toBe(true);
        expect(previewHtml.value).toBe('<p>Content</p>');
    });

    it('togglePreview toggles isPreviewMode off without rendering', async () => {
        const { isPreviewMode, togglePreview } = useMarkdownPreview();

        isPreviewMode.value = true;
        await togglePreview('# Content');

        expect(isPreviewMode.value).toBe(false);
        expect(postMock).not.toHaveBeenCalled();
    });

    it('toggleFullPreview toggles isFullPreview and renders markdown when enabled', async () => {
        postMock.mockResolvedValueOnce({ data: { html: '<p>Full</p>' } });

        const { isFullPreview, previewHtml, toggleFullPreview } = useMarkdownPreview();

        await toggleFullPreview('# Full');

        expect(isFullPreview.value).toBe(true);
        expect(previewHtml.value).toBe('<p>Full</p>');
    });

    it('toggleFullPreview toggles isFullPreview off without rendering', async () => {
        const { isFullPreview, toggleFullPreview } = useMarkdownPreview();

        isFullPreview.value = true;
        await toggleFullPreview('# Full');

        expect(isFullPreview.value).toBe(false);
        expect(postMock).not.toHaveBeenCalled();
    });

    it('uses custom routeName when provided', async () => {
        postMock.mockResolvedValueOnce({ data: { html: '<p>ok</p>' } });

        const { renderMarkdown } = useMarkdownPreview('custom.route');
        await renderMarkdown('content');

        expect((global as any).route).toHaveBeenCalledWith('custom.route');
    });
});
