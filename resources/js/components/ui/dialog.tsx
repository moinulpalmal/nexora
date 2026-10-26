import { router } from '@inertiajs/react';
import { Slot } from '@radix-ui/react-slot';
import { XIcon } from 'lucide-react';
import type { ComponentProps, ReactNode, RefObject } from 'react';
import {
    createContext,
    use,
    useCallback,
    useEffect,
    useId,
    useRef,
    useState,
} from 'react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

/**
 * A native `<dialog>` opened with `showModal()`, styled with daisyUI's `modal`.
 *
 * The browser supplies everything Radix's Dialog was doing: the top layer, a
 * focus trap, `inert` on the rest of the page, and returning focus to whatever
 * opened it. daisyUI adds the surface and the scroll lock (`--page-scroll-lock`),
 * and paints its own backdrop, hiding `::backdrop`.
 *
 * **A modal here never light-dismisses.** Both of the browser's escape routes are
 * refused: there is no `.modal-backdrop` form, so a backdrop click does nothing,
 * and `cancel` is `preventDefault()`ed, so Escape does nothing. Every dialog in
 * this application holds either typed work or a destructive confirmation, and a
 * stray click outside one was throwing that away. Menus are the opposite case and
 * keep their light dismiss — `dropdown-menu.tsx` and `combobox.tsx` are
 * deliberately not covered by this rule; see [§8.7](../../../../ARCHITECTURE.md).
 *
 * That makes the close button the *only* way out, which is why it is not optional
 * — there is no `showCloseButton` prop to turn it off with.
 *
 * **Escape is refused twice, not forever.** Chrome's close watcher lets a page
 * cancel a limited run of close requests and then forces one through: the first
 * two Escapes do nothing, the third closes the panel, and clicking inside first
 * does not reset the count (measured, not guessed). That backstop cannot be
 * disabled from script, and it is the browser guaranteeing a user is never
 * trapped. Treat one Escape doing nothing as the behaviour this rule buys.
 *
 * **A closed dialog holds no state.** `DialogContent` mounts its children when the
 * panel opens and unmounts them when it closes, so every `defaultValue` re-seeds
 * from props and every `useState` inside re-initialises on the next open. This is
 * load-bearing, not an optimisation: the children used to be mounted permanently,
 * which meant Cancel discarded nothing and reopening a form showed the edit you had
 * abandoned rather than the row the server holds. It was worst where a dialog kept
 * React state of its own — a repeater's added and removed rows survived both
 * Cancel *and* a successful save. `DialogClose` stays outside
 * the guard: the only exit is never conditional. The same remount, driven by a
 * `key` instead of by closing, is how a form modal clears itself *without* closing
 * — see ARCHITECTURE.md §8.10.
 *
 * **`busy` is the one narrowing of "the close button is never conditional".** A
 * form modal disables both exits while its save is in flight (§8.10), so the panel
 * cannot be dismissed between the request leaving and the answer arriving — which
 * would otherwise close the dialog over a save that then lands, or over one that is
 * about to be refused. It is *disabled*, never removed: the moment the request
 * settles the X is back. `FormDialogFooter` is the only thing that sets it, through
 * the context below; a dialog with no form footer never touches it, so the
 * confirmation and preview dialogs are unaffected.
 */
type DialogContextValue = {
    open: boolean;
    setOpen: (open: boolean) => void;
    dialogRef: RefObject<HTMLDialogElement | null>;
    titleId: string;
    descriptionId: string;
    /** True while a form inside the panel is submitting — see the docblock. */
    busy: boolean;
    setBusy: (busy: boolean) => void;
};

const DialogContext = createContext<DialogContextValue | null>(null);

function useDialog(): DialogContextValue {
    const context = use(DialogContext);

    if (!context) {
        throw new Error('Dialog parts must be rendered inside <Dialog>.');
    }

    return context;
}

/**
 * The setter for the panel's busy flag, for a form footer to mirror its own
 * `processing` into. Narrower than the whole context on purpose: nothing outside
 * `dialog.tsx` should be reaching for `setOpen`, which is what `DialogClose` is
 * for. The setter is `useState`'s, so it is stable and safe as an effect
 * dependency.
 */
function useDialogBusy(): (busy: boolean) => void {
    return useDialog().setBusy;
}

type DialogProps = {
    children?: ReactNode;
    open?: boolean;
    defaultOpen?: boolean;
    onOpenChange?: (open: boolean) => void;
};

function Dialog({
    children,
    open: openProp,
    defaultOpen = false,
    onOpenChange,
}: DialogProps) {
    const [uncontrolledOpen, setUncontrolledOpen] = useState(defaultOpen);
    const [busy, setBusy] = useState(false);
    const dialogRef = useRef<HTMLDialogElement>(null);
    const id = useId();

    const isControlled = openProp !== undefined;
    const open = isControlled ? openProp : uncontrolledOpen;

    /*
     * Mirrors of state the unmount cleanup below has to read *as it is then*, not
     * as it was when the effect was attached. Written in an effect rather than
     * during render, which ARCHITECTURE.md §8.4 records the React Compiler lint
     * rules reject.
     */
    const openRef = useRef(open);
    const navigatingRef = useRef(false);

    useEffect(() => {
        openRef.current = open;
    });

    /**
     * Say so when a panel disappears rather than being closed.
     *
     * A dialog is mounted by the row it belongs to, so a refresh that drops that
     * row — renamed and re-sorted off the page, or deleted by someone else —
     * unmounts the panel mid-use. React does this silently, and a modal that
     * vanishes with no explanation reads as a bug; the user simply clicks again
     * and gets the same nothing.
     *
     * **Leaving the page is not a vanished row**, and the two look identical from
     * here: clicking a nav link with a modal open unmounts it exactly the same
     * way. They are told apart by URL — a visit to a *different* URL is a
     * navigation, while `router.reload()` re-visits the one we are on, which is
     * precisely the case that must still toast. `finish` clears the flag on a
     * turn of the event loop because the unmount happens in the same render pass
     * the response drives, and would otherwise see the flag already cleared.
     *
     * Under `strictMode` the effect mounts, unmounts and remounts immediately;
     * `openRef` is false at that point for every dialog that starts closed, which
     * is all of them bar an explicitly `defaultOpen` one.
     */
    useEffect(() => {
        const stopStart = router.on('start', (event) => {
            navigatingRef.current =
                event.detail.visit.url.href !== window.location.href;
        });

        const stopFinish = router.on('finish', () => {
            setTimeout(() => {
                navigatingRef.current = false;
            }, 0);
        });

        return () => {
            stopStart();
            stopFinish();

            if (openRef.current && !navigatingRef.current) {
                toast.error(
                    'That record is no longer in this list — it may have been changed or removed.',
                );
            }
        };
    }, []);

    const setOpen = useCallback(
        (next: boolean) => {
            if (!isControlled) {
                setUncontrolledOpen(next);
            }

            onOpenChange?.(next);
        },
        [isControlled, onOpenChange],
    );

    return (
        <DialogContext
            value={{
                open,
                setOpen,
                dialogRef,
                titleId: `${id}-title`,
                descriptionId: `${id}-description`,
                busy,
                setBusy,
            }}
        >
            {children}
        </DialogContext>
    );
}

function DialogTrigger({
    asChild = false,
    onClick,
    ...props
}: ComponentProps<'button'> & { asChild?: boolean }) {
    const { open, setOpen } = useDialog();
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            data-slot="dialog-trigger"
            aria-haspopup="dialog"
            aria-expanded={open}
            onClick={(event) => {
                onClick?.(event);
                setOpen(true);
            }}
            {...props}
        />
    );
}

function DialogClose({
    asChild = false,
    onClick,
    ...props
}: ComponentProps<'button'> & { asChild?: boolean }) {
    const { setOpen } = useDialog();
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            data-slot="dialog-close"
            // Never `<form method="dialog">`: closers sit inside the consumer's own
            // form in places like the form dialogs, and forms cannot nest.
            type="button"
            onClick={(event) => {
                onClick?.(event);
                setOpen(false);
            }}
            {...props}
        />
    );
}

type DialogContentProps = ComponentProps<'div'> & {
    /** Position of the panel — maps to daisyUI's `modal-*` placement classes. */
    position?: 'middle' | 'top' | 'bottom' | 'start' | 'end';
    dialogClassName?: string;
    /**
     * Scroll the panel's *last* child instead of the panel itself, so whatever
     * sits above it stays put.
     *
     * daisyUI's `.modal-box` is already `max-height: calc(100vh - 5em);
     * overflow-y: auto`, which means a tall form scrolls its own Save button out
     * of reach. This turns that off on the box and hands the height budget to the
     * final grid row, which the consumer makes scrollable.
     *
     * **It assumes the panel is title, description, then one flexible child** —
     * the shape every form dialog here already has. The consumer owes that child
     * its own `min-h-0`, `overflow-y-auto` and `data-test="dialog-scroll-body"`,
     * the handle the no-scroll browser assertions use. Setting the flag
     * without that child clips the overflow with no scrollbar at all.
     * Opt-in precisely because most panels are short enough that the
     * box scrolling is fine, and changing all of them at once would be a change
     * nobody asked for.
     *
     * Note the height budget it hands over is now `calc(100vh - 2rem)`, not
     * daisyUI's `calc(100vh - 5em)` — `app.css` reclaims that for every modal.
     *
     * The single column track is untouched either way — see below for why it
     * matters.
     */
    scrollBody?: boolean;
};

function DialogContent({
    className,
    children,
    position = 'middle',
    dialogClassName,
    scrollBody = false,
    ...props
}: DialogContentProps) {
    const { open, setOpen, dialogRef, titleId, descriptionId, busy } =
        useDialog();

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog) {
            return;
        }

        // Guarded so React's double-invoked effects cannot call showModal() twice,
        // which throws InvalidStateError.
        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    }, [open, dialogRef]);

    return (
        <dialog
            ref={dialogRef}
            data-slot="dialog"
            aria-labelledby={titleId}
            aria-describedby={descriptionId}
            className={cn('modal', `modal-${position}`, dialogClassName)}
            // Escape reaches a `showModal()` dialog as `cancel`; refusing it is
            // what stops the key from closing the panel. `close` still runs when
            // something calls `dialog.close()` — the state sync below.
            onCancel={(event) => event.preventDefault()}
            onClose={() => setOpen(false)}
        >
            <div
                data-slot="dialog-content"
                // `relative` is load-bearing: daisyUI's `.modal-box` is static, so
                // without it the absolutely-positioned close button anchors to
                // `.modal` — which is `position: fixed; inset: 0` — and lands in the
                // viewport corner instead of the panel's.
                // The text resets matter as much: a dialog rendered inside a
                // `<th>` (a table header cell) otherwise inherits its
                // nowrap/bold/muted text, and the one-track grid stops a long
                // line from widening the box past its max-width.
                className={cn(
                    'relative modal-box grid grid-cols-[minmax(0,1fr)] gap-4 text-left font-normal whitespace-normal text-base-content normal-case',
                    // `overflow-y-hidden` is the half that does the work: without it
                    // `.modal-box`'s own scrollbar still carries the whole panel and
                    // the inner region never reaches its own overflow.
                    scrollBody &&
                        'grid-rows-[auto_auto_minmax(0,1fr)] overflow-y-hidden',
                    className,
                )}
                {...props}
            >
                {/* Mounted only while open — see "A closed dialog holds no
                    state" in the docblock. */}
                {open && children}

                {/* The only way out — see the docblock. Never conditional;
                    `disabled` while a save is in flight is the one narrowing,
                    and it lifts the moment the request settles. */}
                <DialogClose
                    aria-label="Close"
                    disabled={busy}
                    className="btn absolute top-4 right-4 btn-circle btn-ghost btn-sm"
                >
                    <XIcon className="size-4" />
                </DialogClose>
            </div>
        </dialog>
    );
}

function DialogHeader({ className, ...props }: ComponentProps<'div'>) {
    return (
        <div
            data-slot="dialog-header"
            className={cn(
                'flex flex-col gap-2 text-center sm:text-left',
                className,
            )}
            {...props}
        />
    );
}

function DialogFooter({ className, ...props }: ComponentProps<'div'>) {
    return (
        <div
            data-slot="dialog-footer"
            className={cn(
                'modal-action flex-col-reverse sm:flex-row',
                className,
            )}
            {...props}
        />
    );
}

function DialogTitle({ className, ...props }: ComponentProps<'h2'>) {
    const { titleId } = useDialog();

    return (
        <h2
            id={titleId}
            data-slot="dialog-title"
            className={cn('text-lg leading-none font-semibold', className)}
            {...props}
        />
    );
}

function DialogDescription({ className, ...props }: ComponentProps<'p'>) {
    const { descriptionId } = useDialog();

    return (
        <p
            id={descriptionId}
            data-slot="dialog-description"
            className={cn('text-sm text-base-content/60', className)}
            {...props}
        />
    );
}

export {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    useDialogBusy,
};
