import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * A native checkbox: better assistive-technology support than Radix's
 * `button[role="checkbox"]` plus hidden input, and it submits with the form
 * exactly the same way.
 */
function Checkbox({
    className,
    indeterminate = false,
    ...props
}: ComponentProps<'input'> & {
    /**
     * The third state a "select all" box needs: some of the rows below are ticked,
     * but not all of them.
     *
     * **Applied through a ref, because it cannot be applied any other way.**
     * `indeterminate` is a DOM *property* with no matching HTML attribute, so
     * passing it in JSX would render a dead `indeterminate="true"` attribute and
     * leave the box looking unticked. It is deliberately not part of `checked`:
     * the box still reports whatever `checked` says when clicked, and the striped
     * rendering is purely what the reader sees.
     */
    indeterminate?: boolean;
}) {
    return (
        <input
            type="checkbox"
            data-slot="checkbox"
            ref={(element) => {
                if (element !== null) {
                    element.indeterminate = indeterminate;
                }
            }}
            className={cn(
                'peer checkbox checkbox-sm checkbox-primary',
                className,
            )}
            {...props}
        />
    );
}

export { Checkbox };
