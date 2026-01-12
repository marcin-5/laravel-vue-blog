/**
 * Selects a random motto from a newline-separated list of mottos.
 * Mottos are separated by double newlines (\n\n).
 *
 * @param mottoText - String containing mottos separated by double newlines
 * @returns A randomly selected motto, or null if input is empty/invalid
 */
export function selectRandomMotto(mottoText: string | null | undefined): string | null {
    if (!mottoText) return null;

    const mottoList = mottoText.split('\n\n').filter((motto) => motto.trim());
    if (mottoList.length === 0) return null;

    const randomIndex = Math.floor(Math.random() * mottoList.length);
    return mottoList[randomIndex].trim();
}

// Checks whether a string-like value has non-empty content after trimming.
// Returns true only when the value is a non-empty string (post-trim).
export function hasContent(value: string | null | undefined): boolean {
    return (value?.trim().length ?? 0) > 0;
}
