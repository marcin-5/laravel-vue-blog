import { onMounted, onUnmounted, ref } from 'vue';

export function useCssVariables(variableNames: string[]) {
    const variables = ref<Record<string, string>>({});

    const updateVariables = () => {
        const style = getComputedStyle(document.documentElement);
        variables.value = Object.fromEntries(variableNames.map((name) => [name, style.getPropertyValue(name).trim()]));
    };

    let observer: MutationObserver | null = null;

    onMounted(() => {
        updateVariables();

        // Monitor dark mode changes via MutationObserver on <html> class
        observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    updateVariables();
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    });

    onUnmounted(() => {
        observer?.disconnect();
    });

    return {
        variables,
        updateVariables,
    };
}
