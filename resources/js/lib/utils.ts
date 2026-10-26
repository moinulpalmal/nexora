import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';
import type { DaisyClassGroupId } from '@/lib/daisy-class-groups';
import { daisyClassGroups } from '@/lib/daisy-class-groups';

const twMerge = extendTailwindMerge<DaisyClassGroupId>({
    extend: {
        classGroups: daisyClassGroups,
    },
});

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * How many rows a block of `rowCount` rows claims inside a `field-grid`.
 *
 * Bucketed rather than exact, because Tailwind cannot generate a class from a
 * runtime value and a literal `row-span-${n}` would be purged. The approximation
 * is safe under `auto-rows-min`: an under-sized bucket self-corrects, because the
 * spanned tracks grow to fit, and an over-sized one only leaves whitespace.
 *
 * For a form dialog and its read-only detail twin, which must place their group
 * blocks identically — see ARCHITECTURE.md §8.11.
 */
export function fieldRowSpan(rowCount: number): string {
    if (rowCount <= 2) {
        return 'field-span-2';
    }

    return rowCount <= 5 ? 'field-span-3' : 'field-span-4';
}
