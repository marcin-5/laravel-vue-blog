if (typeof window !== 'undefined') {
    if (!window.performance) {
        (window as any).performance = {};
    }
    if (!window.performance.getEntriesByType) {
        window.performance.getEntriesByType = () => [];
    }
}
