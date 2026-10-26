import { useCombobox, useMultipleSelection } from 'downshift';
import {
    Check,
    ChevronsUpDown,
    Info,
    LoaderCircle,
    Search,
    X,
} from 'lucide-react';
import {
    useCallback,
    useEffect,
    useId,
    useLayoutEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import { useOptionSearch } from '@/hooks/use-option-search';
import { ANCHOR_GAP, positionAnchored } from '@/lib/anchored-position';
import { cn } from '@/lib/utils';

/**
 * A searchable select, replacing every native `<select>` in the application.
 *
 * Built on `downshift`, which supplies the ARIA combobox semantics — roles,
 * `aria-activedescendant`, keyboard navigation and result announcements. Those
 * are the reason a package was bought rather than this being hand-rolled the way
 * `dropdown-menu.tsx` was; that file's own docblock records the rule.
 *
 * A few things are worth knowing before using it:
 *
 * 1. **It submits through a hidden `<input>`.** Every form in this app is an
 *    uncontrolled `<Form {...submit}>` reading `name=` off native elements, and a
 *    `<div role="combobox">` submits nothing. The hidden input is what keeps that
 *    model working — it is load-bearing, not a detail.
 *
 *    **While `disabled`, the hidden input is not rendered at all**, so the
 *    control submits nothing — what a native element does, and what the
 *    trigger's own `disabled` had always implied. Until this was fixed only the
 *    trigger carried the attribute, so a picker the person could not touch went
 *    on posting its last value and the save was refused on a greyed-out field.
 *
 *    The consequence for call sites is that **`disabled` means *absent*, not
 *    *empty***. A field ruled `required` fails the same either way, but one
 *    ruled `present` needs its own `<input type="hidden" value="">` instead —
 *    or the caller unmounts its picker rather
 *    than disabling it. ARCHITECTURE.md §8.5.
 * 2. **The search box only appears above {@link SEARCH_THRESHOLD} options.** A
 *    filter input in front of a two-option Active/Inactive choice is slower than
 *    the plain listbox it replaced, so short lists do not get one. One component,
 *    one constant, no per-call-site judgement.
 * 3. **`searchUrl` is opt-in.** Without it every option is filtered in the
 *    browser, which is right until a list outgrows being shipped whole.
 * 4. **`InputClick` is suppressed, in both variants.** downshift's own reducer
 *    treats a click on the input as `isOpen: !isOpen`, which is right when the
 *    input *is* the control and wrong here, where it lives inside the open menu:
 *    clicking the search box to type in it closed the menu. Every `useCombobox`
 *    below therefore needs a `stateReducer` holding `isOpen` steady for that one
 *    action type. Clicking the toggle button still closes the menu, and outside
 *    click and Escape still dismiss it — a menu holds no typed work, so it is
 *    deliberately not covered by the modal rule in `dialog.tsx`.
 * 5. **In `SingleCombobox` the search query is only ever what the user typed.**
 *    downshift, given a controlled `selectedItem`, keeps copying that item's
 *    *label* into `inputValue` — on select, on open, on every re-render whose
 *    `selectedItem` prop changed identity. With `searchUrl` set that label is
 *    then fired at the `?q=` endpoint, which prefix-matches nothing, empties
 *    the results and blanks the trigger even though a value is selected.
 *    `onInputValueChange` therefore honours only `InputChange`. `MultiCombobox`
 *    never hits this — it hands downshift `selectedItem: null`.
 */

/** Below this many options, no search input is rendered. */
export const SEARCH_THRESHOLD = 10;

/**
 * What an option means, shown as a tooltip beside it while it is highlighted
 * (mouse or arrow keys), and beside the closed trigger for the chosen one (owner,
 * 2026-10-12). Plain data rather than JSX, so a server can send it.
 */
export type ComboboxOptionDetails = {
    title?: string;
    description?: string;
    lines?: { label: string; value: string }[];
    note?: string;
};

export type ComboboxOption = {
    value: string | number;
    label: string;
    /** Secondary text shown after the label — a code, a status. */
    hint?: string;
    disabled?: boolean;
    /** An explanation of the option — {@link ComboboxOptionDetails}. */
    details?: ComboboxOptionDetails | null;
};

type SharedProps = {
    /** Name of the hidden input, i.e. the key the server reads. */
    name?: string;
    options: ComboboxOption[];
    placeholder?: string;
    /** Wayfinder URL taking `?q=`; enables server-side option search. */
    searchUrl?: string;
    id?: string;
    className?: string;
    disabled?: boolean;
    required?: boolean;
    /**
     * Set from the server's errors, the way `Input` takes it — the trigger paints
     * `select-error` so a rejected choice looks rejected, not merely described by
     * the message underneath it. See ARCHITECTURE.md §8.5 and §8.10.
     */
    'aria-invalid'?: boolean;
    /**
     * Focus the trigger on mount. A form modal's first control must carry this —
     * a "Save & add another" remount would otherwise drop focus to the document
     * (ARCHITECTURE.md §8.10).
     */
    autoFocus?: boolean;
    'aria-label'?: string;
    'data-test'?: string;
};

type SingleProps = SharedProps & {
    multiple?: false;
    value?: string | number | null;
    defaultValue?: string | number | null;
    onChange?: (value: string | number | null) => void;
    /**
     * Whether a chosen value can be emptied — a × in the trigger, or Backspace /
     * Delete on it (owner, 2026-10-12). On by default for every combobox that is
     * not `required`; pass `false` where empty only snaps back to a default
     * (ARCHITECTURE.md §8.5).
     */
    clearable?: boolean;
};

type MultipleProps = SharedProps & {
    multiple: true;
    value?: Array<string | number>;
    defaultValue?: Array<string | number>;
    onChange?: (value: Array<string | number>) => void;
};

export default function Combobox(props: SingleProps | MultipleProps) {
    return props.multiple === true ? (
        <MultiCombobox {...props} />
    ) : (
        <SingleCombobox {...props} />
    );
}

/* -------------------------------------------------------------------------- */
/* Single select                                                              */
/* -------------------------------------------------------------------------- */

function SingleCombobox({
    name,
    options,
    placeholder = 'Choose…',
    searchUrl,
    id,
    className,
    disabled = false,
    required = false,
    value,
    defaultValue = null,
    onChange,
    clearable = true,
    // Destructured only to keep it out of `rest`: it is the discriminant, not a
    // DOM attribute, and spreading it onto the trigger `<button>` makes React
    // hydrate a `multiple` attribute the server never rendered.
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    multiple,
    ...rest
}: SingleProps) {
    const [uncontrolled, setUncontrolled] = useState<string | number | null>(
        defaultValue,
    );
    const isControlled = value !== undefined;
    const selectedValue = isControlled ? value : uncontrolled;

    const [query, setQuery] = useState('');
    const remote = useOptionSearch(searchUrl, query);
    const visible = useVisibleOptions(
        options,
        query,
        remote.options,
        searchUrl,
    );

    // Whether the pointer or focus is on the closed trigger — for its tooltip.
    const [pointing, setPointing] = useState(false);
    const tooltipId = useId();

    /*
     * The selected option may not be in `visible` — it has been filtered out, or
     * an async page no longer contains it. Fall back to the full list so the
     * button keeps showing a label rather than going blank mid-search.
     */
    const selected =
        options.find((option) => sameValue(option.value, selectedValue)) ??
        remote.options.find((option) =>
            sameValue(option.value, selectedValue),
        ) ??
        null;

    const showSearch =
        searchUrl !== undefined || options.length >= SEARCH_THRESHOLD;

    const {
        isOpen,
        getToggleButtonProps,
        getMenuProps,
        getInputProps,
        getItemProps,
        highlightedIndex,
        selectItem,
    } = useCombobox({
        items: visible,
        itemToString: (option) => option?.label ?? '',
        selectedItem: selected,
        inputValue: query,
        onInputValueChange: ({ inputValue, type }) => {
            // Only a real keystroke in the search box drives the query.
            // downshift also copies the *selected item's label* into
            // `inputValue` — on select, on open, and whenever the controlled
            // `selectedItem` prop changes identity (which it does every time an
            // async result lands). Mirroring that into `query` fires
            // `useOptionSearch` for the label, a term the `?q=` endpoint
            // prefix-matches nothing against, so `remote.options` empties and
            // the trigger falls back to its placeholder even though a value is
            // selected. `MultiCombobox` sidesteps this by never handing
            // downshift a `selectedItem`; the single-select equivalent is to
            // ignore every `inputValue` change that is not the user typing.
            if (type === useCombobox.stateChangeTypes.InputChange) {
                setQuery(inputValue ?? '');
            }
        },
        onIsOpenChange: ({ isOpen: nowOpen }) => {
            if (!nowOpen) {
                setQuery('');
            }
        },
        onSelectedItemChange: ({ selectedItem }) => {
            const next = selectedItem?.value ?? null;

            if (!isControlled) {
                setUncontrolled(next);
            }

            onChange?.(next);
        },
        stateReducer: (state, { changes, type }) =>
            type === useCombobox.stateChangeTypes.InputClick
                ? { ...changes, isOpen: state.isOpen }
                : changes,
    });

    const { anchorRef, menuRef, searchRef } = useAnchoredMenu(
        isOpen,
        showSearch,
    );
    /*
     * downshift's prop getters only merge these ref objects into callback refs —
     * nothing reads `.current` during render — so the React Compiler's
     * `react-hooks/refs` report on passing a ref to a function is a false positive.
     */
    // eslint-disable-next-line react-hooks/refs
    const menuProps = getMenuProps({ ref: menuRef });
    const inputProps = getInputProps({ ref: searchRef });

    /*
     * `id` goes *through* the prop getter rather than being spread before it:
     * downshift supplies its own, and letting that win would silently break
     * every `<Label htmlFor>` pointing at this control.
     */
    /*
     * A required control is never emptied here — the browser's own message is
     * the only way it may stay blank — and a disabled one offers nothing.
     */
    const canClear =
        clearable &&
        !required &&
        !disabled &&
        selectedValue !== null &&
        selectedValue !== '';

    /*
     * The explanation tooltip: the highlighted option's while the menu is open
     * (it follows the mouse and the arrow keys alike, since both move downshift's
     * highlight), the chosen option's while the closed trigger is hovered or
     * focused. Options without `details` show nothing.
     */
    const tipDetails = isOpen
        ? highlightedIndex >= 0
            ? visible[highlightedIndex]?.details
            : undefined
        : pointing
          ? selected?.details
          : undefined;
    const resolveTipAnchor = (): HTMLElement | null =>
        isOpen
            ? (menuRef.current?.querySelectorAll<HTMLElement>(
                  'li[role="option"]',
              )[highlightedIndex] ?? null)
            : anchorRef.current;

    // Same false positive as `getMenuProps` above: the ref is merged, not read.
    // eslint-disable-next-line react-hooks/refs
    const toggleProps = getToggleButtonProps({
        ref: anchorRef,
        id,
        'aria-describedby': tipDetails ? tooltipId : undefined,
        onMouseEnter: () => setPointing(true),
        onMouseLeave: () => setPointing(false),
        onFocus: () => setPointing(true),
        onBlur: () => setPointing(false),
        onKeyDown: (event) => {
            if (
                canClear &&
                !isOpen &&
                (event.key === 'Backspace' || event.key === 'Delete')
            ) {
                event.preventDefault();
                selectItem(null);
            }
        },
    });

    return (
        <div className="relative">
            {/* Not rendered while disabled — a disabled control submits
                nothing, and leaving this behind posted a value nobody could
                change. See the docblock: `disabled` means absent, not empty. */}
            {name !== undefined && !disabled && (
                <input
                    type="hidden"
                    name={name}
                    value={selectedValue ?? ''}
                    /* Mirrors the trigger so a missing required value still
                       blocks submission and reports on the right control. */
                    required={required}
                />
            )}

            <button
                type="button"
                disabled={disabled}
                // `className` styles the control, not the wrapper — the same
                // contract as `Input`, so `select-sm` and widths land where a
                // call site expects them to.
                className={cn(
                    'select w-full items-center justify-between text-left',
                    // Mirrors `Input`'s `input-error`, so a rejected choice
                    // reads the same as a rejected text field.
                    rest['aria-invalid'] === true && 'select-error',
                    selected === null && 'text-base-content/50',
                    className,
                )}
                {...rest}
                {...toggleProps}
            >
                <span className="truncate">
                    {selected?.label ?? placeholder}
                </span>
                <span className="flex shrink-0 items-center gap-1">
                    {/* Inside the trigger, as the multi-select's chip × is —
                        a span, since a button may not hold a button. */}
                    {canClear && (
                        <span
                            role="button"
                            tabIndex={-1}
                            aria-label={`Clear ${selected?.label ?? 'the selection'}`}
                            className="cursor-pointer opacity-50 hover:opacity-100"
                            data-test="combobox-clear"
                            onClick={(event) => {
                                // Otherwise the toggle button opens the menu.
                                event.stopPropagation();
                                selectItem(null);
                            }}
                        >
                            <X className="size-3.5" />
                        </span>
                    )}
                    <ChevronsUpDown className="size-4 opacity-50" />
                </span>
            </button>

            <MenuShell
                menuProps={menuProps}
                showSearch={showSearch}
                inputProps={inputProps}
                loading={remote.loading}
                isOpen={isOpen}
            >
                {visible.length === 0 && <EmptyRow loading={remote.loading} />}

                {visible.map((option, index) => (
                    <OptionRow
                        key={option.value}
                        option={option}
                        highlighted={highlightedIndex === index}
                        selected={sameValue(option.value, selectedValue)}
                        {...getItemProps({ item: option, index })}
                    />
                ))}
            </MenuShell>

            <OptionTooltip
                id={tooltipId}
                details={tipDetails}
                resolveAnchor={resolveTipAnchor}
                side={isOpen ? 'right' : 'bottom'}
            />
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* Multi select                                                               */
/* -------------------------------------------------------------------------- */

function MultiCombobox({
    name,
    options,
    placeholder = 'Choose…',
    searchUrl,
    id,
    className,
    disabled = false,
    value,
    defaultValue,
    onChange,
    /* See SingleCombobox — the discriminant must not reach the DOM. */
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    multiple,
    ...rest
}: MultipleProps) {
    const [uncontrolled, setUncontrolled] = useState<Array<string | number>>(
        defaultValue ?? [],
    );
    const isControlled = value !== undefined;
    const selectedValues = isControlled ? value : uncontrolled;

    const [query, setQuery] = useState('');
    const remote = useOptionSearch(searchUrl, query);

    const selectedItems = useMemo(() => {
        // Remote results are folded in so a chip keeps its label after the
        // option that produced it has been searched away.
        const pool =
            searchUrl === undefined ? options : [...options, ...remote.options];

        return selectedValues
            .map((entry) =>
                pool.find((option) => sameValue(option.value, entry)),
            )
            .filter((option): option is ComboboxOption => option !== undefined);
    }, [options, remote.options, searchUrl, selectedValues]);

    const commit = useCallback(
        (next: Array<string | number>) => {
            if (!isControlled) {
                setUncontrolled(next);
            }

            onChange?.(next);
        },
        [isControlled, onChange],
    );

    const filtered = useVisibleOptions(
        options,
        query,
        remote.options,
        searchUrl,
    );
    // Already-chosen options drop out of the menu rather than appearing ticked;
    // they are visible as chips directly above it.
    const visible = filtered.filter(
        (option) =>
            !selectedValues.some((entry) => sameValue(entry, option.value)),
    );

    const { getSelectedItemProps, getDropdownProps, removeSelectedItem } =
        useMultipleSelection<ComboboxOption>({
            selectedItems,
            onSelectedItemsChange: ({ selectedItems: next }) =>
                commit((next ?? []).map((option) => option.value)),
        });

    const showSearch =
        searchUrl !== undefined || options.length >= SEARCH_THRESHOLD;

    const {
        isOpen,
        getToggleButtonProps,
        getMenuProps,
        getInputProps,
        getItemProps,
        highlightedIndex,
    } = useCombobox({
        items: visible,
        itemToString: (option) => option?.label ?? '',
        inputValue: query,
        // Multi-select keeps the menu open so several can be picked in a row.
        selectedItem: null,
        onInputValueChange: ({ inputValue }) => setQuery(inputValue ?? ''),
        onIsOpenChange: ({ isOpen: nowOpen }) => {
            if (!nowOpen) {
                setQuery('');
            }
        },
        onSelectedItemChange: ({ selectedItem }) => {
            if (selectedItem === null || selectedItem === undefined) {
                return;
            }

            commit([...selectedValues, selectedItem.value]);
        },
        stateReducer: (state, { changes, type }) => {
            if (type === useCombobox.stateChangeTypes.InputClick) {
                return { ...changes, isOpen: state.isOpen };
            }

            return type === useCombobox.stateChangeTypes.ItemClick ||
                type === useCombobox.stateChangeTypes.InputKeyDownEnter
                ? { ...changes, isOpen: true, inputValue: '' }
                : changes;
        },
    });

    const { anchorRef, menuRef, searchRef } = useAnchoredMenu(
        isOpen,
        showSearch,
    );
    const menuProps = getMenuProps({ ref: menuRef });
    const inputProps = getInputProps(
        getDropdownProps({ preventKeyAction: isOpen, ref: searchRef }),
    );

    /* See the note in SingleCombobox — downshift's own `id` must not win. */
    const toggleProps = getToggleButtonProps({ ref: anchorRef, id });

    return (
        <div className="relative">
            {/* One hidden input per selection, so the server receives an array
                exactly as a checkbox list would have sent it.

                A trailing `[]` on `name` is stripped first, so `name="roles"`
                and `name="roles[]"` both emit `roles[]`. The second spelling
                is the natural one to write coming from plain HTML, and without
                this it silently produced `roles[][]` — which decodes to an
                array of arrays and fails validation on every submit, while
                server-side tests posting arrays directly stay green.

                None of them is rendered while disabled, the same contract
                `SingleCombobox` keeps: a disabled picker must not go on
                posting the selections underneath it.
                */}
            {name !== undefined &&
                !disabled &&
                selectedValues.map((entry) => (
                    <input
                        key={entry}
                        type="hidden"
                        name={`${name.replace(/\[\]$/, '')}[]`}
                        value={entry}
                    />
                ))}

            <button
                type="button"
                disabled={disabled}
                className={cn(
                    'select h-auto min-h-10 w-full items-center justify-between gap-2 py-1.5 text-left',
                    rest['aria-invalid'] === true && 'select-error',
                    className,
                )}
                {...rest}
                {...toggleProps}
            >
                <span className="flex flex-wrap items-center gap-1">
                    {selectedItems.length === 0 && (
                        <span className="text-base-content/50">
                            {placeholder}
                        </span>
                    )}

                    {selectedItems.map((option, index) => (
                        <span
                            key={option.value}
                            className="badge gap-1 badge-ghost badge-sm"
                            {...getSelectedItemProps({
                                selectedItem: option,
                                index,
                            })}
                        >
                            {option.label}
                            {/* A disabled control's chips are not removable
                                either — the remove button sits inside the
                                disabled trigger, so leaving it live is an
                                affordance the control does not have. */}
                            {!disabled && (
                                <span
                                    role="button"
                                    tabIndex={-1}
                                    aria-label={`Remove ${option.label}`}
                                    className="cursor-pointer opacity-60 hover:opacity-100"
                                    onClick={(event) => {
                                        // Otherwise the toggle button opens the menu.
                                        event.stopPropagation();
                                        removeSelectedItem(option);
                                    }}
                                >
                                    <X className="size-3" />
                                </span>
                            )}
                        </span>
                    ))}
                </span>

                <ChevronsUpDown className="size-4 shrink-0 opacity-50" />
            </button>

            <MenuShell
                menuProps={menuProps}
                showSearch={showSearch}
                inputProps={inputProps}
                loading={remote.loading}
                isOpen={isOpen}
            >
                {visible.length === 0 && <EmptyRow loading={remote.loading} />}

                {visible.map((option, index) => (
                    <OptionRow
                        key={option.value}
                        option={option}
                        highlighted={highlightedIndex === index}
                        selected={false}
                        {...getItemProps({ item: option, index })}
                    />
                ))}
            </MenuShell>
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* Shared pieces                                                              */
/* -------------------------------------------------------------------------- */

/**
 * Whether an option's value is the selected one.
 *
 * Compared as strings rather than by identity, because a value makes a round trip
 * through the DOM and does not always come back the type it left as: a server-side
 * `int` id arrives in `options` as a number, while a call site that seeded
 * `defaultValue` from a form-shaped string holds `'3'`. `===` reads those as
 * different, so the trigger falls back to its placeholder while the hidden input
 * still submits the right id — a bug that looks like data loss and is not; see
 * ARCHITECTURE.md §8.5.
 *
 * `null` and `undefined` match nothing, including each other: "no selection" is not
 * a value an option can hold.
 */
function sameValue(
    a: string | number | null | undefined,
    b: string | number | null | undefined,
): boolean {
    return (
        a !== null &&
        a !== undefined &&
        b !== null &&
        b !== undefined &&
        String(a) === String(b)
    );
}

/**
 * The floating listbox, in the top layer so it escapes `overflow` containers.
 *
 * `popover="manual"` rather than `"auto"`: light dismiss would close the menu
 * behind downshift's back and desynchronise its `isOpen` state, so opening and
 * closing are driven from that state instead.
 */
function MenuShell({
    menuProps,
    showSearch,
    inputProps,
    loading,
    isOpen,
    children,
}: {
    menuProps: Record<string, unknown>;
    showSearch: boolean;
    inputProps: Record<string, unknown>;
    loading: boolean;
    isOpen: boolean;
    children: React.ReactNode;
}) {
    return (
        <div
            popover="manual"
            className={cn(
                'fixed inset-auto z-50 m-0 w-(--anchor-width) min-w-56 rounded-box bg-base-100 p-1 shadow-lg ring-1 ring-base-300',
                !isOpen && 'hidden',
            )}
            {...(menuProps as Record<string, never>)}
        >
            {/* The input stays mounted when hidden: downshift owns its ref and
                keyboard handlers, and unmounting it would break arrow keys on
                short lists. */}
            <div className={cn('p-1', !showSearch && 'sr-only')}>
                <label className="input w-full input-sm">
                    <Search className="size-4 opacity-50" />
                    <input
                        {...(inputProps as Record<string, never>)}
                        placeholder="Search…"
                    />
                    {loading && (
                        <LoaderCircle className="size-4 animate-spin opacity-50" />
                    )}
                </label>
            </div>

            <ul className="max-h-64 overflow-y-auto">{children}</ul>
        </div>
    );
}

function OptionRow({
    option,
    highlighted,
    selected,
    ...props
}: {
    option: ComboboxOption;
    highlighted: boolean;
    selected: boolean;
} & Record<string, unknown>) {
    return (
        <li
            className={cn(
                'flex cursor-pointer items-center justify-between gap-2 rounded-field px-3 py-1.5 text-sm',
                highlighted && 'bg-base-200',
                option.disabled && 'cursor-not-allowed opacity-50',
            )}
            {...(props as Record<string, never>)}
        >
            <span className="truncate">
                {option.label}
                {option.hint !== undefined && (
                    <span className="ml-2 font-mono text-xs text-base-content/50">
                        {option.hint}
                    </span>
                )}
            </span>

            <span className="flex shrink-0 items-center gap-1">
                {/* Says there is an explanation to read beside it. */}
                {option.details && <Info className="size-3 opacity-40" />}
                {selected && <Check className="size-4" />}
            </span>
        </li>
    );
}

/**
 * An option's explanation beside its anchor — the highlighted option, or the
 * closed trigger for the chosen one. A `popover`, so it sits in the top layer
 * above the menu (itself a popover); it shows whenever `details` and an anchor are
 * both there, and hides otherwise.
 */
function OptionTooltip({
    id,
    details,
    resolveAnchor,
    side,
}: {
    id: string;
    details: ComboboxOptionDetails | null | undefined;
    resolveAnchor: () => HTMLElement | null;
    side: 'right' | 'bottom';
}) {
    const ref = useRef<HTMLDivElement>(null);

    useLayoutEffect(() => {
        const tip = ref.current;

        if (tip === null) {
            return;
        }

        const anchor = details ? resolveAnchor() : null;

        if (anchor === null) {
            if (tip.matches(':popover-open')) {
                tip.hidePopover();
            }

            return;
        }

        if (!tip.matches(':popover-open')) {
            tip.showPopover();
        }

        positionAnchored(anchor, tip, {
            side,
            sideOffset: ANCHOR_GAP,
            widthVar: '--tooltip-anchor-width',
        });
    });

    return (
        <div
            ref={ref}
            id={id}
            popover="manual"
            role="tooltip"
            className="pointer-events-none fixed inset-auto z-50 m-0 max-w-80 rounded-box bg-base-100 p-3 text-xs shadow-lg ring-1 ring-base-300"
            data-test="combobox-option-tooltip"
        >
            {details && (
                <div className="grid gap-1.5">
                    {details.title !== undefined && (
                        <p className="text-sm font-semibold">{details.title}</p>
                    )}
                    {details.description !== undefined && (
                        <p className="text-base-content/80">
                            {details.description}
                        </p>
                    )}
                    {(details.lines ?? []).length > 0 && (
                        <dl className="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5">
                            {(details.lines ?? []).map((line) => (
                                <div key={line.label} className="contents">
                                    <dt className="text-base-content/60">
                                        {line.label}
                                    </dt>
                                    <dd className="break-words">
                                        {line.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    )}
                    {details.note !== undefined && (
                        <p className="text-warning">{details.note}</p>
                    )}
                </div>
            )}
        </div>
    );
}

function EmptyRow({ loading }: { loading: boolean }) {
    return (
        <li className="px-3 py-2 text-sm text-base-content/60">
            {loading ? 'Searching…' : 'No matches.'}
        </li>
    );
}

/**
 * Which options the menu shows: the server's results when searching remotely,
 * otherwise a local case-insensitive match on label and hint.
 */
function useVisibleOptions(
    options: ComboboxOption[],
    query: string,
    remoteOptions: ComboboxOption[],
    searchUrl?: string,
): ComboboxOption[] {
    return useMemo(() => {
        if (searchUrl !== undefined) {
            /*
             * Fall back to the options the page was rendered with until the
             * first remote result lands. Without this, opening the menu shows
             * an empty list for the length of the debounce every time.
             */
            return remoteOptions.length === 0 && query.trim() === ''
                ? options
                : remoteOptions;
        }

        const term = query.trim().toLowerCase();

        if (term === '') {
            return options;
        }

        return options.filter(
            (option) =>
                option.label.toLowerCase().includes(term) ||
                (option.hint ?? '').toLowerCase().includes(term),
        );
    }, [options, query, remoteOptions, searchUrl]);
}

/**
 * Show the popover while open, and keep it pinned to its trigger.
 *
 * Repositioning on scroll and resize is what `position: fixed` in the top layer
 * costs; see `lib/anchored-position.ts`.
 */
function useAnchoredMenu(isOpen: boolean, focusSearch: boolean = false) {
    const anchorRef = useRef<HTMLButtonElement>(null);
    const menuRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        const anchor = anchorRef.current;
        const menu = menuRef.current;

        if (!anchor || !menu) {
            return;
        }

        if (!isOpen) {
            if (menu.matches(':popover-open')) {
                // Return focus to the trigger so the next Tab doesn't restart
                // from the top of the page after the menu's DOM is hidden.
                if (menu.contains(document.activeElement)) {
                    anchor.focus();
                }

                menu.hidePopover();
            }

            return;
        }

        if (!menu.matches(':popover-open')) {
            menu.showPopover();
        }

        const place = (): void =>
            positionAnchored(anchor, menu, { sideOffset: ANCHOR_GAP });

        place();

        if (focusSearch) {
            searchRef.current?.focus();
        }

        window.addEventListener('resize', place);
        window.addEventListener('scroll', place, true);

        return () => {
            window.removeEventListener('resize', place);
            window.removeEventListener('scroll', place, true);
        };
    }, [isOpen, focusSearch]);

    return { anchorRef, menuRef, searchRef };
}

/** Stable ids for call sites that need to label the control themselves. */
export function useComboboxId(provided?: string): string {
    const generated = useId();

    return provided ?? generated;
}
