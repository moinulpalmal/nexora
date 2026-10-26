import { THEME_MODES } from '@/lib/enums';
import type { Theme as GeneratedTheme } from '@/lib/enums';

/**
 * daisyUI's 35 built-in themes, derived from `@/lib/enums`'s `THEME_MODES` —
 * generated from `App\Enums\Theme` (ARCHITECTURE.md §8.2) — rather than
 * hand-mirrored a second time.
 *
 * `THEMES` below is the daisyUI-only subset of `THEME_MODES`: `system` is not
 * a daisyUI theme and carries no `color-scheme` of its own to report here —
 * it resolves to Light or Dark in the browser, see `resolveTheme()` in
 * `use-appearance.tsx`.
 *
 * @see https://daisyui.com/docs/themes/
 */
export const THEMES = Object.fromEntries(
    Object.entries(THEME_MODES).filter(([name]) => name !== 'system'),
) as Record<Exclude<GeneratedTheme, 'system'>, 'light' | 'dark'>;

/** A concrete daisyUI theme name. */
export type ResolvedTheme = keyof typeof THEMES;

/** A stored preference: a theme name, or `system` to follow the OS. */
export type Theme = GeneratedTheme;

export const THEME_NAMES = Object.keys(THEMES) as ResolvedTheme[];

export const isResolvedTheme = (value: string): value is ResolvedTheme =>
    value in THEMES;

export const isDarkTheme = (theme: ResolvedTheme): boolean =>
    THEMES[theme] === 'dark';

/** One selectable theme, as described by `App\Enums\Theme`. */
export type ThemeOption = {
    value: ResolvedTheme;
    label: string;
    isDark: boolean;
};
