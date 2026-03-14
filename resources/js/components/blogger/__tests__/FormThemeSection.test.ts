import type { ThemeTranslations } from '@/composables/useThemeSection';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import FormThemeSection from '../FormThemeSection.vue';

vi.mock('@/components/blogger/FormColorField.vue', () => ({
    default: {
        name: 'FormColorField',
        props: ['id', 'label', 'modelValue', 'placeholder', 'tooltip', 'error'],
        template: '<div class="form-color-field" :id="id" @click="$emit(\'update:model-value\', \'#ffffff\')">{{ label }}</div>',
    },
}));

vi.mock('@/components/blogger/FormSelectField.vue', () => ({
    default: {
        name: 'FormSelectField',
        props: ['id', 'label', 'modelValue', 'options'],
        template: '<div class="form-select-field" :id="id" @click="$emit(\'update:model-value\', \'new-font\')">{{ label }}</div>',
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button @click="$emit(\'click\')"><slot /></button>',
    },
}));

vi.mock('@/components/ui/popover', () => ({
    Popover: { template: '<div><slot /></div>' },
    PopoverTrigger: { template: '<slot />' },
    PopoverContent: { template: '<div><slot /></div>' },
}));

vi.mock('@/components/ui/slider', () => ({
    Slider: {
        name: 'Slider',
        props: ['modelValue'],
        template: '<div class="slider" @click="$emit(\'update:model-value\', [110])"></div>',
    },
}));

vi.mock('@/components/ui/tooltip', () => ({
    TooltipButton: {
        name: 'TooltipButton',
        props: ['tooltipContent'],
        template: '<button class="tooltip-button" @click="$emit(\'click\')"><slot /></button>',
    },
}));

vi.mock('lucide-vue-next', () => ({
    Download: { name: 'Download', template: '<div class="download-icon" />' },
    Upload: { name: 'Upload', template: '<div class="upload-icon" />' },
    Settings2: { name: 'Settings2', template: '<div class="settings-icon" />' },
}));

const mockToast = vi.fn();

vi.mock('@/composables/useToast', () => ({
    useToast: () => ({
        toast: mockToast,
    }),
}));

vi.mock('@/composables/useCssVariables', () => ({
    useCssVariables: () => ({
        variables: {
            '--background': '#ffffff',
            '--primary': '#000000',
        },
    }),
}));

const translations: ThemeTranslations = {
    background: 'Background Color',
    backgroundTooltip: 'Tooltip for background',
    primary: 'Primary Color',
    primaryTooltip: 'Tooltip for primary',
    foreground: 'Foreground',
    foregroundTooltip: 'Foreground tooltip',
    primaryForeground: 'Primary Foreground',
    primaryForegroundTooltip: 'Primary Foreground tooltip',
    secondary: 'Secondary',
    secondaryTooltip: 'Secondary tooltip',
    secondaryForeground: 'Secondary Foreground',
    secondaryForegroundTooltip: 'Secondary Foreground tooltip',
    mutedForeground: 'Muted Foreground',
    mutedForegroundTooltip: 'Muted Foreground tooltip',
    border: 'Border',
    borderTooltip: 'Border tooltip',
    link: 'Link',
    linkTooltip: 'Link tooltip',
    linkHover: 'Link Hover',
    linkHoverTooltip: 'Link Hover tooltip',
    breadcrumbLink: 'Breadcrumb Link',
    breadcrumbLinkTooltip: 'Breadcrumb Link tooltip',
    breadcrumbLinkActive: 'Breadcrumb Link Active',
    breadcrumbLinkActiveTooltip: 'Breadcrumb Link Active tooltip',
    card: 'Card',
    cardTooltip: 'Card tooltip',
    fontHeader: 'Header Font',
    fontBody: 'Body Font',
    fontMotto: 'Motto Font',
    fontExcerpt: 'Excerpt Font',
    fontFooter: 'Footer Font',
    fontScaleCorrection: 'Size correction',
    importTooltip: 'Import Theme',
    exportTooltip: 'Export Theme',
    importSuccess: 'Import successful',
    importError: 'Import failed',
};

const baseProps = {
    title: 'Light Theme',
    idPrefix: 'blog-theme-light',
    translations,
    colors: {
        '--background': '#ffffff',
        '--primary': '#000000',
        '--font-header': 'inherit',
    },
};

function mountComponent() {
    return mount(FormThemeSection, {
        props: baseProps,
    });
}

function getFirstEmittedColors(wrapper: ReturnType<typeof mountComponent>) {
    return wrapper.emitted('update:colors')?.[0]?.[0];
}

function mockExportEnvironment() {
    const createObjectURL = vi.fn(() => 'blob:url');
    const revokeObjectURL = vi.fn();

    vi.stubGlobal('URL', {
        createObjectURL,
        revokeObjectURL,
    });

    const anchorElement = document.createElement('a');
    const anchorClick = vi.spyOn(anchorElement, 'click').mockImplementation(() => {});
    const createElementSpy = vi.spyOn(document, 'createElement').mockImplementation((tagName: string) => {
        if (tagName === 'a') {
            return anchorElement;
        }

        return document.createElement(tagName);
    });

    return {
        createObjectURL,
        revokeObjectURL,
        anchorClick,
        restore() {
            createElementSpy.mockRestore();
            vi.unstubAllGlobals();
        },
    };
}

describe('FormThemeSection.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });
    it('renders with correct title', () => {
        const wrapper = mountComponent();

        expect(wrapper.find('h4').text()).toBe('Light Theme');
    });

    it('renders color fields from translations', () => {
        const wrapper = mountComponent();

        const colorFields = wrapper.findAll('.form-color-field');

        expect(colorFields.length).toBeGreaterThan(0);
        expect(colorFields[0].text()).toContain('Primary Color');
    });

    it('emits update:colors when a color field is updated', async () => {
        const wrapper = mountComponent();

        await wrapper.find('.form-color-field').trigger('click');

        expect(wrapper.emitted('update:colors')).toBeTruthy();
        expect(getFirstEmittedColors(wrapper)).toMatchObject({
            '--primary': '#ffffff',
        });
    });

    it('emits update:colors when a font field is updated', async () => {
        const wrapper = mountComponent();

        await wrapper.find('.form-select-field').trigger('click');

        expect(wrapper.emitted('update:colors')).toBeTruthy();
        expect(getFirstEmittedColors(wrapper)).toMatchObject({
            '--font-header': 'new-font',
        });
    });

    it('exports theme when export button is clicked', async () => {
        const wrapper = mountComponent();
        const exportEnvironment = mockExportEnvironment();

        try {
            const exportButton = wrapper.find('.download-icon').element.closest('button') as HTMLButtonElement | null;

            expect(exportButton).not.toBeNull();

            await exportButton!.click();

            expect(exportEnvironment.createObjectURL).toHaveBeenCalled();
            expect(exportEnvironment.anchorClick).toHaveBeenCalled();
            expect(exportEnvironment.revokeObjectURL).toHaveBeenCalled();
        } finally {
            exportEnvironment.restore();
        }
    });

    it('handles theme import successfully', async () => {
        const wrapper = mountComponent();
        const importedTheme = {
            '--background': '#000000',
            '--primary': '#ffffff',
        };

        const file = new File([JSON.stringify(importedTheme)], 'theme.json', { type: 'application/json' });
        file.text = vi.fn().mockResolvedValue(JSON.stringify(importedTheme));

        const input = wrapper.find('input[type="file"]');

        Object.defineProperty(input.element, 'files', {
            value: [file],
            configurable: true,
        });

        await input.trigger('change');
        await new Promise((resolve) => setTimeout(resolve, 0));

        expect(wrapper.emitted('update:colors')).toBeTruthy();
        expect(getFirstEmittedColors(wrapper)).toMatchObject(importedTheme);
        expect(mockToast).toHaveBeenCalledWith({
            title: 'Import successful',
            variant: 'default',
        });
    });
});
