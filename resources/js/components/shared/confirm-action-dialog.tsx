import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { RouteFormDefinition } from '@/wayfinder';

type ButtonVariant = ComponentProps<typeof Button>['variant'];

/**
 * Asks before submitting a Wayfinder form, then submits it.
 *
 * The trigger is whatever you pass as `children` — an icon button in a table
 * row, a full-width button in a modal. `ConfirmDeleteDialog` is the destructive
 * preset over this.
 *
 * **It closes itself when the action succeeds.** Most callers never needed that:
 * the record leaves the refreshed list and the whole subtree unmounts with the
 * dialog inside it. But an action that changes nothing on the page has nothing to
 * tear the panel down, and it sat open over a finished job: a controller that
 * answers `back()` leaves the page — and the dialog — standing.
 *
 * **`onSuccess` is for a confirmation raised from inside another dialog** —
 * ARCHITECTURE.md §8.11. It runs as well as the close above, not instead of it:
 * what it closes is the *parent* panel the confirmation was stacked on, which has
 * to be closed deliberately rather than torn down underneath the user.
 *
 * **`fields` is for an action that carries a payload** —
 * hidden inputs rendered inside the form, which a bulk confirmation needs: the
 * ticked ids are the action. Without it the only way to send them was the query
 * string, which puts two hundred ids in the page address.
 */
export default function ConfirmActionDialog({
    submit,
    title,
    description,
    confirmLabel,
    confirmVariant = 'destructive',
    disabled = false,
    testId,
    onSuccess,
    fields,
    children,
}: {
    submit: RouteFormDefinition<'post'>;
    title: string;
    description: string;
    confirmLabel: string;
    confirmVariant?: ButtonVariant;
    disabled?: boolean;
    testId?: string;
    /** Run when the action succeeded, before this panel closes. */
    onSuccess?: () => void;
    /** Hidden inputs submitted with the action — a bulk action's ticked ids. */
    fields?: ReactNode;
    children: ReactNode;
}) {
    // Above the early return: a `disabled` that flips must not change hook order.
    const [open, setOpen] = useState(false);

    // A disabled trigger still renders — it just never opens the dialog.
    if (disabled) {
        return <>{children}</>;
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>

            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <Form
                    {...submit}
                    onSuccess={() => {
                        setOpen(false);
                        onSuccess?.();
                    }}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <>
                            {fields}
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    variant={confirmVariant}
                                    type="submit"
                                    disabled={processing}
                                    data-test={testId && `confirm-${testId}`}
                                >
                                    {confirmLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
