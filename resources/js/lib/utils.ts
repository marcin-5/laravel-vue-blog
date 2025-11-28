import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// Checks whether a string-like value has non-empty content after trimming.
// Returns true only when the value is a non-empty string (post-trim).
export function hasContent(value: string | null | undefined): boolean {
    return (value?.trim().length ?? 0) > 0;
}
