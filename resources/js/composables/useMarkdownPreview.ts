import { type MarkdownPreviewSection, type PreviewLayout } from '@/types/blog.types';
import { useHttp } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, type Ref } from 'vue';

export function useMarkdownPreview(
    routeName: string = 'markdown.preview',
    debounceMs: number = 300,
): MarkdownPreviewSection {
    const isPreviewMode = ref(false);
    const isFullPreview = ref(false);
    const previewLayout = ref<PreviewLayout>('vertical');
    const previewHtml = ref('');
    const MARKDOWN_RENDER_ERROR_HTML = '<p class="text-error">Error rendering markdown</p>';
    const http = useHttp<
        {
            content: string;
        },
        { html: string }
    >({ content: '' });

    async function fetchMarkdownPreview(content: string): Promise<string> {
        if (!content) return '';

        try {
            http.content = content;
            const response = await http.post(route(routeName));
            return response.html;
        } catch (error) {
            console.error('Markdown preview fetch error:', error);
            throw new Error('Failed to fetch markdown preview');
        }
    }

    async function renderMarkdown(content: string) {
        if (!content) {
            previewHtml.value = '';
            return;
        }
        try {
            previewHtml.value = await fetchMarkdownPreview(content);
        } catch (error) {
            console.error('Failed to render markdown:', error);
            previewHtml.value = MARKDOWN_RENDER_ERROR_HTML;
        }
    }

    const handleInput = useDebounceFn((content: string) => {
        void renderMarkdown(content);
    }, debounceMs);

    async function toggleModeAndRender(mode: Ref<boolean>, content: string) {
        mode.value = !mode.value;
        if (mode.value) {
            await renderMarkdown(content);
        }
    }

    async function togglePreview(content: string) {
        await toggleModeAndRender(isPreviewMode, content);
    }

    async function toggleFullPreview(content: string) {
        await toggleModeAndRender(isFullPreview, content);
    }

    function setLayout(layout: PreviewLayout) {
        previewLayout.value = layout;
    }

    return {
        isPreviewMode,
        isFullPreview,
        previewLayout,
        previewHtml,
        renderMarkdown,
        togglePreview,
        toggleFullPreview,
        setLayout,
        handleInput,
    };
}
