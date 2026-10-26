import { X } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

/**
 * The strip that appears over a list once rows are ticked — "N selected", a way to
 * clear it, and whatever the surface wants done to the selection.
 *
 * Pairs with `hooks/use-row-selection.ts`; ARCHITECTURE.md §8.6 records the rules
 * they share.
 *
 * **It is not an action registry, deliberately.** It renders a count, a Clear and
 * the buttons its caller passes as `children`. The one caller today offers a single
 * action; a second table wanting bulk deactivate adds its own button and its own
 * blockers, and nothing here tries to anticipate what those will be.
 *
 * **`note` is where a constraint is stated**, not a disabled tooltip: a selection
 * that cannot grow any further should say why in a place a reader is already
 * looking, beside the count that just changed.
 *
 * Announced with `role="status"` / `aria-live="polite"` because the count changes
 * with every tick and is otherwise silent to a screen reader. `polite`, not
 * `assertive`: it is feedback on an action just taken, never an interruption.
 */
export default function BulkActionBar({
    count,
    onClear,
    note,
    children,
}: {
    /** How many rows are ticked. The bar renders nothing at zero. */
    count: number;
    onClear: () => void;
    /** A one-line constraint shown beside the count, e.g. why other rows are greyed. */
    note?: string;
    /** The action buttons for this selection. */
    children: ReactNode;
}) {
    if (count === 0) {
        return null;
    }

    return (
        <div
            role="status"
            aria-live="polite"
            className="flex flex-wrap items-center justify-between gap-3 rounded-box border border-primary/30 bg-primary/5 px-3 py-2"
            data-test="bulk-action-bar"
        >
            <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span
                    className="text-sm font-medium"
                    data-test="bulk-selected-count"
                >
                    {count === 1 ? '1 selected' : `${count} selected`}
                </span>

                {note !== undefined && (
                    <span className="text-xs text-base-content/60">{note}</span>
                )}
            </div>

            <div className="flex items-center gap-2">
                {children}

                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    onClick={onClear}
                    data-test="clear-selection"
                >
                    <X /> Clear
                </Button>
            </div>
        </div>
    );
}
