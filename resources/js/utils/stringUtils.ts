// Checks whether a string-like value has non-empty content after trimming.
// Returns true only when the value is a non-empty string (post-trim).
export function hasContent(value: string | null | undefined): boolean {
    return (value?.trim().length ?? 0) > 0;
}
