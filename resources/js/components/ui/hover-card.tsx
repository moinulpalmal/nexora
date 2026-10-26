import type { ReactNode } from 'react';
import { useEffect, useId, useLayoutEffect, useRef, useState } from 'react';
import { ANCHOR_GAP, positionAnchored } from '@/lib/anchored-position';
import type { Side } from '@/lib/anchored-position';
import { cn } from '@/lib/utils';

/** How long the pointer must rest on the trigger before the card opens. */
const OPEN_DELAY = 250;

/** Grace period so moving between the trigger's own children does not flicker the card. */
const CLOSE_DELAY = 100;

/**
 * A rich, read-only tooltip: opens when the pointer rests on its trigger or
 * when focus moves into it, closes on leave, blur or Escape.
 *
 * It renders in the top layer with the Popover API and is placed by
 * `positionAnchored`, the same mechanism `combobox.tsx` uses for its option
 * tooltips — so it escapes `overflow: hidden` without a portal, and flips to
 * stay inside the viewport without a positioning library. The card is only
 * mounted while open, so a list of a hundred triggers carries no hidden nodes.
 */
export function HoverCard({
    content,
    children,
    side = 'right',
    className,
    testId = 'hover-card',
}: {
    content: ReactNode;
    children: ReactNode;
    side?: Side;
    className?: string;
    testId?: string;
}) {
    const [open, setOpen] = useState(false);
    const triggerRef = useRef<HTMLSpanElement>(null);
    const cardRef = useRef<HTMLDivElement>(null);
    const timer = useRef<number | undefined>(undefined);
    const id = useId();

    const schedule = (next: boolean, delay: number) => {
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => setOpen(next), delay);
    };

    useEffect(() => () => window.clearTimeout(timer.current), []);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [open]);

    useLayoutEffect(() => {
        const card = cardRef.current;
        const trigger = triggerRef.current;

        if (!open || card === null || trigger === null) {
            return;
        }

        if (!card.matches(':popover-open')) {
            card.showPopover();
        }

        positionAnchored(trigger, card, { side, sideOffset: ANCHOR_GAP });
    }, [open, side]);

    return (
        <span
            ref={triggerRef}
            className={cn('inline-flex', className)}
            aria-describedby={open ? id : undefined}
            onPointerEnter={() => schedule(true, OPEN_DELAY)}
            onPointerLeave={() => schedule(false, CLOSE_DELAY)}
            onFocus={() => schedule(true, 0)}
            onBlur={() => schedule(false, 0)}
        >
            {children}

            {open && (
                <div
                    ref={cardRef}
                    id={id}
                    popover="manual"
                    role="tooltip"
                    className="pointer-events-none fixed inset-auto z-50 m-0 max-w-80 rounded-box bg-base-100 p-3 text-xs shadow-lg ring-1 ring-base-300"
                    data-test={testId}
                >
                    {content}
                </div>
            )}
        </span>
    );
}
