import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { handleContentClick } from '../domUtils';

// Mock inertia router
vi.mock('@inertiajs/vue3', () => ({
    router: {
        visit: vi.fn(),
    },
}));

describe('domUtils', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        window.history.replaceState(null, '', '/');
    });

    it('should intercept internal link clicks', () => {
        const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
        });

        const link = document.createElement('a');
        link.href = `${window.location.origin}/about`;
        document.body.appendChild(link);

        // Mock event.target
        Object.defineProperty(event, 'target', { value: link });
        const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

        handleContentClick(event);

        expect(preventDefaultSpy).toHaveBeenCalled();
        expect(router.visit).toHaveBeenCalledWith('/about');

        document.body.removeChild(link);
    });

    it('should not intercept external link clicks', () => {
        const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
        });

        const link = document.createElement('a');
        link.href = 'https://google.com';
        document.body.appendChild(link);

        Object.defineProperty(event, 'target', { value: link });
        const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

        handleContentClick(event);

        expect(preventDefaultSpy).not.toHaveBeenCalled();
        expect(router.visit).not.toHaveBeenCalled();

        document.body.removeChild(link);
    });

    it('should not intercept clicks with modifier keys', () => {
        const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            ctrlKey: true,
        });

        const link = document.createElement('a');
        link.href = `${window.location.origin}/about`;
        document.body.appendChild(link);

        Object.defineProperty(event, 'target', { value: link });
        const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

        handleContentClick(event);

        expect(preventDefaultSpy).not.toHaveBeenCalled();
        expect(router.visit).not.toHaveBeenCalled();

        document.body.removeChild(link);
    });

    it('should not intercept links with target="_blank"', () => {
        const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
        });

        const link = document.createElement('a');
        link.href = `${window.location.origin}/about`;
        link.target = '_blank';
        document.body.appendChild(link);

        Object.defineProperty(event, 'target', { value: link });
        const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

        handleContentClick(event);

        expect(preventDefaultSpy).not.toHaveBeenCalled();
        expect(router.visit).not.toHaveBeenCalled();

        document.body.removeChild(link);
    });

    it('should not intercept same page hash links', () => {
        const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
        });

        const link = document.createElement('a');
        link.href = `${window.location.origin}/#section`;
        document.body.appendChild(link);

        Object.defineProperty(event, 'target', { value: link });
        const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

        handleContentClick(event);

        expect(preventDefaultSpy).not.toHaveBeenCalled();
        expect(router.visit).not.toHaveBeenCalled();

        document.body.removeChild(link);
    });

    it('should find link if click target is inside a link', () => {
        const event = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
        });

        const link = document.createElement('a');
        link.href = `${window.location.origin}/about`;
        const span = document.createElement('span');
        link.appendChild(span);
        document.body.appendChild(link);

        Object.defineProperty(event, 'target', { value: span });
        const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

        handleContentClick(event);

        expect(preventDefaultSpy).toHaveBeenCalled();
        expect(router.visit).toHaveBeenCalledWith('/about');

        document.body.removeChild(link);
    });
});
