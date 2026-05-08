export function formatStageDescription(description: string, values: Record<string, string | number>): string {
    return Object.entries(values).reduce((result, [key, value]) => {
        return result.replaceAll(`%${key}`, String(value));
    }, description);
}
