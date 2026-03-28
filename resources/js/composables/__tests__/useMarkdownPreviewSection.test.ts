import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useMarkdownPreviewSection } from '../useMarkdownPreviewSection';

// Mock Inertia useHttp hook
const postMock = vi.fn();
vi.mock('@inertiajs/vue3', () => ({
    useHttp: () => ({
        post: postMock,
    }),
}));

(global as any).route = vi.fn(() => 'mock-route');

describe('useMarkdownPreviewSection', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        postMock.mockReset();
    });

    it('initializes with default values', () => {
        const { isPreviewMode, isFullPreview, previewLayout, previewHtml } = useMarkdownPreviewSection();

        expect(isPreviewMode.value).toBe(false);
        expect(isFullPreview.value).toBe(false);
        expect(previewLayout.value).toBe('vertical');
        expect(previewHtml.value).toBe('');
    });

    it('exposes handleInput as an alias for renderMarkdown', async () => {
        postMock.mockResolvedValueOnce({ data: { html: '<p>Handled</p>' } });

        const { previewHtml, handleInput } = useMarkdownPreviewSection();
        await handleInput('# Handled');

        expect(previewHtml.value).toBe('<p>Handled</p>');
    });

    it('handleInput clears previewHtml when content is empty', async () => {
        const { previewHtml, handleInput } = useMarkdownPreviewSection();

        previewHtml.value = 'existing html';
        await handleInput('');

        expect(previewHtml.value).toBe('');
    });

    it('setLayout updates previewLayout', () => {
        const { previewLayout, setLayout } = useMarkdownPreviewSection();

        setLayout('horizontal');
        expect(previewLayout.value).toBe('horizontal');
    });

    it('togglePreview toggles isPreviewMode and renders markdown', async () => {
        postMock.mockResolvedValueOnce({ data: { html: '<p>Preview</p>' } });

        const { isPreviewMode, previewHtml, togglePreview } = useMarkdownPreviewSection();
        await togglePreview('# Preview');

        expect(isPreviewMode.value).toBe(true);
        expect(previewHtml.value).toBe('<p>Preview</p>');
    });

    it('toggleFullPreview toggles isFullPreview and renders markdown', async () => {
        postMock.mockResolvedValueOnce({ data: { html: '<p>Full</p>' } });

        const { isFullPreview, previewHtml, toggleFullPreview } = useMarkdownPreviewSection();
        await toggleFullPreview('# Full');

        expect(isFullPreview.value).toBe(true);
        expect(previewHtml.value).toBe('<p>Full</p>');
    });

    it('uses custom routeName when provided', async () => {
        postMock.mockResolvedValueOnce({ data: { html: '<p>ok</p>' } });

        const { handleInput } = useMarkdownPreviewSection('custom.route');
        await handleInput('content');

        expect((global as any).route).toHaveBeenCalledWith('custom.route');
    });
});
