import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { ListFilters } from '@/types';
import type { QueryParams, RouteQueryOptions } from '@/wayfinder';

/**
 * How long a filter cell waits after the last keystroke before it visits.
 *
 * The same 400ms `use-option-search.ts` uses, and it matters more here: text
 * cells are `%term%` matches, so every visit this saves is a table scan that
 * never runs.
 */
const DEBOUNCE_MS = 400;

/**
 * How long after a refresh the *focus* trigger stays quiet.
 *
 * Focus is incidental — a second monitor, a devtools panel, alt-tabbing to a
 * spreadsheet — so without a floor a user who never touches the app generates a
 * visit every few seconds, on every list screen they have open. Thirty seconds
 * is short enough that a real context switch (leave to edit a record, come back)
 * always refreshes, and long enough that idle window churn costs nothing.
 *
 * **Deliberately not applied to the dialog trigger.** Opening a modal is a
 * deliberate act and the moment stale options do damage; throttling it would
 * reproduce the bug this whole mechanism exists to fix — see ARCHITECTURE.md §8.6.
 */
const FOCUS_THROTTLE_MS = 30_000;

/**
 * Whether a modal is on screen right now.
 *
 * Dialogs here are native `<dialog>` opened with `showModal()` (§8.7), so the
 * browser itself maintains the `open` attribute — there is no React state to
 * consult and no context to thread, and this sees *every* dialog including the
 * confirmation and reorder panels that do not use `useFormDialog`.
 */
function aDialogIsOpen(): boolean {
    return document.querySelector('dialog[open]') !== null;
}

type Query = QueryParams;

/** Whatever Wayfinder hands `router.get` — an object, not a bare string. */
type Destination = Parameters<typeof router.get>[0];

/** A Wayfinder `index` helper, which is what every list page passes in. */
type UrlBuilder = (options: RouteQueryOptions) => Destination;

/**
 * The query state and handlers every Admin list screen wires up.
 *
 * Owns four things the list pages would otherwise each reimplement: the
 * debounce in front of the text cells, resetting to page 1 whenever a filter
 * changes, clearing every cell in **one** visit rather than one per cell, and
 * keeping a mounted page's props from going stale.
 *
 * That last one is three triggers sharing one guard — the Refresh button, the
 * window regaining focus, and any dialog opening. A list screen left open while
 * its master data changed elsewhere used to need a manual reload before a form
 * modal would offer the new records; it no longer does. ARCHITECTURE.md §8.6.
 *
 * The cells' text is held here as a draft and only pushed on the debounce, so a
 * round trip never lands mid-word and clobbers what someone is still typing.
 * Nothing syncs the draft back from the server inside an effect — the draft is
 * what was sent, and `clear()` is the one thing that resets it.
 *
 * @param filters     The surface's `filters` prop, straight from `ListRequest::filters()`.
 * @param url         The surface's Wayfinder `index`, called as `url({ query })`.
 * @param only        Props to re-fetch on a *filter* visit — a partial reload, so a
 *                    keystroke does not reserialise the whole page. It must name
 *                    **every** prop that varies with the filtered rows, or that
 *                    prop silently goes stale.
 * @param refreshOnly Props to re-fetch on a `refresh()` visit — the Refresh
 *                    button, the focus listener and the dialog observer alike.
 *                    **Omit it and those three do a full reload instead**; it does
 *                    *not* fall back to `only`. These lists differ on purpose:
 *                    `only` is sized for a keystroke, where nothing but the rows
 *                    can have changed since the last visit. `refresh()` is called
 *                    after arbitrary time has passed — another user may have
 *                    edited data an unrelated prop was built from (a pending
 *                    import banner, a buyer dropdown, a tab count) — so it must
 *                    name every prop derived from a query, not just the rows, and
 *                    a page that names nothing gets everything rather than being
 *                    trusted to have judged `only` complete.
 */
export function useListFilters<F extends ListFilters<string | string[]>>({
    filters,
    url,
    only,
    refreshOnly,
}: {
    filters: F;
    url: UrlBuilder;
    only: string[];
    refreshOnly?: string[];
}) {
    const [draft, setDraft] = useState<F['filter']>(filters.filter);
    const [refreshing, setRefreshing] = useState(false);

    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Only a teardown: a debounced visit still pending when the page unmounts
    // would otherwise fire against a component that is gone.
    useEffect(
        () => () => {
            if (timer.current !== null) {
                clearTimeout(timer.current);
            }
        },
        [],
    );

    const go = useCallback(
        (query: Query, partial: boolean) =>
            router.get(
                url({ query }),
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    ...(partial ? { only } : {}),
                },
            ),
        [only, url],
    );

    /**
     * Visit with part of the top-level query changed, resetting to page 1.
     *
     * Any change of what is listed drops the page: staying on page 9 of a
     * result set that now has two pages would show an empty table.
     */
    const visit = useCallback(
        (next: Partial<F>) =>
            go({ ...filters, ...next, page: undefined }, false),
        [filters, go],
    );

    /**
     * Change one filter cell, or several at once. Text cells debounce; dropdowns
     * and checklists (a list of ticked values) go immediately.
     *
     * A cell may have to clear another in the *same* visit — the purchase-order
     * list's buyer and department cells clear its BQS pick, which their cascade
     * may have just dropped from the dropdown. Hence the second call shape, a
     * patch of several columns: `setFilter({ buyer_id: '3', bqs_id: '' })`.
     * Calling the one-column form twice cannot do it, because both calls close
     * over the same `draft` and the second merge discards the first.
     *
     * A patch always visits at once — nothing that clears a sibling cell is a
     * keystroke — so it takes no `debounce` argument.
     */
    const setFilter = useCallback(
        (
            target: string | Record<string, string | string[]>,
            value?: string | string[],
            debounce?: boolean,
        ) => {
            const patch =
                typeof target === 'string'
                    ? { [target]: value as string | string[] }
                    : target;

            // The draft is the authority for the row — it already carries every
            // cell, and merging the server's copy back over it here would undo
            // whatever else is still mid-debounce.
            const merged = { ...draft, ...patch } as F['filter'];

            setDraft(merged);

            if (timer.current !== null) {
                clearTimeout(timer.current);
            }

            const send = () =>
                go({ ...filters, filter: merged, page: undefined }, true);

            if (debounce !== true) {
                send();

                return;
            }

            timer.current = setTimeout(send, DEBOUNCE_MS);
        },
        [draft, filters, go],
    );

    /** Empty every cell in one visit. */
    const clear = useCallback(() => {
        const empty = Object.fromEntries(
            Object.entries(filters.filter).map(([column, value]) => [
                column,
                Array.isArray(value) ? [] : '',
            ]),
        ) as F['filter'];

        setDraft(empty);

        if (timer.current !== null) {
            clearTimeout(timer.current);
        }

        go({ ...filters, filter: empty, page: undefined }, false);
    }, [filters, go]);

    /*
     * `refreshOnly` arrives as an inline array literal from every page, so it is a
     * new identity on every render. Read through a ref, written in an effect rather
     * than during render — §8.4 records that the React Compiler lint rules reject a
     * render-phase ref write — so `refresh` below can be stable and therefore safe
     * as a dependency of the listener and observer effects. Without this the
     * listeners would detach and reattach on every render.
     */
    const refreshOnlyRef = useRef(refreshOnly);
    const inFlight = useRef(false);
    const lastRefreshAt = useRef(0);

    useEffect(() => {
        refreshOnlyRef.current = refreshOnly;
    });

    /**
     * Re-fetch the current page as it stands — same filters, sort, page and
     * scroll position — without changing the URL.
     *
     * **Three callers share this and share its guard**: the toolbar button, the
     * focus listener and the dialog observer. The in-flight flag is a ref rather
     * than the `refreshing` state because a listener closes over the state it was
     * attached with; the state still exists, but only to drive the spinner.
     *
     * **A page with no `refreshOnly` gets a full reload, not `only`.** `only` is
     * sized for a keystroke — nothing but the rows can have changed since the last
     * visit — and none of these three triggers is a keystroke. Correct by
     * construction beats a per-page list that can silently omit a prop, which is the
     * failure this mechanism exists to prevent (ARCHITECTURE.md §8.6).
     */
    const refresh = useCallback(() => {
        if (inFlight.current) {
            return;
        }

        inFlight.current = true;
        lastRefreshAt.current = Date.now();

        router.reload({
            ...(refreshOnlyRef.current ? { only: refreshOnlyRef.current } : {}),
            onStart: () => setRefreshing(true),
            onFinish: () => {
                inFlight.current = false;
                lastRefreshAt.current = Date.now();
                setRefreshing(false);
            },
        });
    }, []);

    /**
     * Self-heal when the window comes back.
     *
     * A mounted page holds the props it was rendered with; nothing tells it that
     * master data it derived a dropdown from has moved. Both events are needed and
     * neither subsumes the other: `visibilitychange` fires when you switch browser
     * tabs, `focus` when you come back from another application with this tab
     * already visible.
     *
     * Skipped while a dialog is open — the dialog trigger below already fetched when
     * it opened, and re-fetching under a live form risks pulling a row, and whatever
     * was typed into it, out from under the user.
     */
    useEffect(() => {
        const onReturn = () => {
            if (document.visibilityState !== 'visible' || aDialogIsOpen()) {
                return;
            }

            if (Date.now() - lastRefreshAt.current < FOCUS_THROTTLE_MS) {
                return;
            }

            refresh();
        };

        document.addEventListener('visibilitychange', onReturn);
        window.addEventListener('focus', onReturn);

        return () => {
            document.removeEventListener('visibilitychange', onReturn);
            window.removeEventListener('focus', onReturn);
        };
    }, [refresh]);

    /**
     * Refresh whenever any dialog opens.
     *
     * **A `MutationObserver` rather than a React handler, and the reason is the
     * platform**: a native `<dialog>` fires `close` but has no `open` event,
     * `useFormDialog` takes a `close` callback and does not own the open state, and
     * the confirmation and reorder dialogs do not use it at all. Watching the
     * attribute the browser itself sets is the one place that sees every dialog on
     * the page, including ones added later by someone who never reads this file.
     *
     * Unthrottled on purpose — see {@link FOCUS_THROTTLE_MS}.
     */
    useEffect(() => {
        const observer = new MutationObserver((records) => {
            const opened = records.some(
                (record) =>
                    record.target instanceof HTMLDialogElement &&
                    record.target.hasAttribute('open'),
            );

            if (opened) {
                refresh();
            }
        });

        observer.observe(document.body, {
            subtree: true,
            attributes: true,
            attributeFilter: ['open'],
        });

        return () => observer.disconnect();
    }, [refresh]);

    const hasActiveFilter = Object.values(filters.filter).some((value) =>
        Array.isArray(value) ? value.length > 0 : value !== '',
    );

    return {
        draft,
        visit,
        setFilter,
        clear,
        hasActiveFilter,
        refresh,
        refreshing,
    };
}
