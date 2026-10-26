import { useState } from 'react';

/** The least a row must carry to be selectable. */
type SelectableRow = { id: number };

/**
 * Tick boxes on a paginated list, for a bulk action over the selection.
 *
 * **The selection belongs to the rows currently rendered.** The header checkbox
 * means *this page*, never "everything matching the filter", and a page or filter
 * change drops it. A selection that outlives the rows it was made from is a bulk
 * action aimed at rows nobody can see — which is exactly when one goes wrong
 * (ARCHITECTURE.md §8.6).
 *
 * **The reset is derived, never an effect.** The ticked ids are stored tagged with
 * a signature built from the rendered row ids, and read back only while that tag
 * still matches; otherwise the selection reads as empty. This is the trick
 * `tna-item-fields.tsx` uses for `sourcePick`, and it is not a style choice — the
 * React Compiler lint rules reject resetting state from an effect. It also gets the
 * edge case right for free: a plain refresh returning the same rows *keeps* the
 * selection, while a new page or filter silently drops it.
 *
 * **`selectable` narrows what may be ticked**, for an action that cannot accept
 * every row — a TNA item move stays within one buyer, so once a row is ticked the
 * others' boxes are disabled rather than allowed into a selection the server would
 * refuse. It is given the already-selected ids so it can answer "may this row join
 * *this* selection", and it is not a security boundary: the request re-checks.
 */
export function useRowSelection<TRow extends SelectableRow>(
    rows: TRow[],
    selectable?: (row: TRow, selected: number[]) => boolean,
) {
    const signature = rows.map((row) => row.id).join(',');

    const [picked, setPicked] = useState<{
        key: string;
        ids: number[];
    } | null>(null);

    const selected = picked?.key === signature ? picked.ids : [];

    const write = (ids: number[]) => setPicked({ key: signature, ids });

    const isSelected = (id: number) => selected.includes(id);

    const canSelect = (row: TRow) =>
        isSelected(row.id) || (selectable?.(row, selected) ?? true);

    const toggle = (id: number) =>
        write(
            selected.includes(id)
                ? selected.filter((current) => current !== id)
                : [...selected, id],
        );

    /** Every row on this page that the action can accept — what "select all" means. */
    const available = rows.filter((row) => canSelect(row));

    const allOnPage =
        available.length > 0 && available.length === selected.length;

    const toggleAll = () =>
        write(allOnPage ? [] : available.map((row) => row.id));

    return {
        selected,
        count: selected.length,
        isSelected,
        canSelect,
        toggle,
        toggleAll,
        allOnPage,
        someOnPage: selected.length > 0 && !allOnPage,
        clear: () => write([]),
    };
}
