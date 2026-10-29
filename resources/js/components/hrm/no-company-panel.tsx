import { usePage } from '@inertiajs/react';
import { Building2 } from 'lucide-react';

/**
 * Drawn in place of a company-scoped list when no company is selected
 * (ARCHITECTURE.md §9.10) — "no access" must never read as "no data".
 *
 * In `components/hrm/` while HRM is its only user; it moves to
 * `components/shared/` when Projects or Accounting imports it (§6.5).
 */
export default function NoCompanyPanel() {
    const { companies } = usePage().props.auth;

    return (
        <div
            role="status"
            className="flex flex-col items-center gap-2 rounded-box border border-dashed border-base-300 px-6 py-12 text-center"
            data-test="no-company"
        >
            <Building2 className="size-8 text-base-content/40" />

            {companies.length === 0 ? (
                <>
                    <p className="font-medium">
                        You have not been given a company yet
                    </p>
                    <p className="max-w-md text-sm text-base-content/60">
                        These records belong to a company. Ask an administrator
                        to grant you one under Admin → Users → Companies.
                    </p>
                </>
            ) : (
                <>
                    <p className="font-medium">Select a company</p>
                    <p className="max-w-md text-sm text-base-content/60">
                        These records belong to a company. Choose the one you
                        are working in from the company switcher in the header.
                    </p>
                </>
            )}
        </div>
    );
}
