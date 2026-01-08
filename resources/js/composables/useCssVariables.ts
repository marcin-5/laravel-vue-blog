import { onMounted, onUnmounted, ref } from 'vue';

export function useCssVariables(variableNames: string[]) {
    const variables = ref<Record<string, string>>({});

    const updateVariables = () => {
        const style = getComputedStyle(document.documentElement);
        const newVariables: Record<string, string> = {};
        variableNames.forEach((name) => {
            newVariables[name] = style.getPropertyValue(name).trim();
        });
        variables.value = newVariables;
    };

    onMounted(() => {
        updateVariables();

        // Monitor dark mode changes via MutationObserver on <html> class
        const observer = new MutationObserver((mutations) => {
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

        onUnmounted(() => {
            observer.disconnect();
        });
    });

    return {
        variables,
        updateVariables,
    };
}
