import { ref } from 'vue';

export function useMarkdownPreview(previewRouteName: string) {
    const isPreviewMode = ref(false);
    const isFullPreview = ref(false);
    const previewLayout = ref<'horizontal' | 'vertical'>('vertical');
    const previewHtml = ref('');

    const MARKDOWN_RENDER_ERROR_HTML = '<p class="text-red-500">Error rendering markdown</p>';

    async function fetchMarkdownPreview(content: string): Promise<string> {
        const response = await fetch(route(previewRouteName), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                content: content,
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to fetch markdown preview');
        }

        const data = await response.json();
        return data.html;
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

    function togglePreview(content: string) {
        isPreviewMode.value = !isPreviewMode.value;
        if (isPreviewMode.value) {
            renderMarkdown(content);
        }
    }

    function toggleFullPreview(content: string) {
        isFullPreview.value = !isFullPreview.value;
        if (isFullPreview.value) {
            renderMarkdown(content);
        }
    }

    function setLayoutHorizontal() {
        previewLayout.value = 'horizontal';
    }

    function setLayoutVertical() {
        previewLayout.value = 'vertical';
    }

    return {
        isPreviewMode,
        isFullPreview,
        previewLayout,
        previewHtml,
        renderMarkdown,
        togglePreview,
        toggleFullPreview,
        setLayoutHorizontal,
        setLayoutVertical,
    };
}
