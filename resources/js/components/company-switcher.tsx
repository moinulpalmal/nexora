import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import { update } from '@/actions/App/Http/Controllers/Settings/CompanySelectionController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

/**
 * The header's "which company am I working in" menu (ARCHITECTURE.md §9.10).
 *
 * Lists only the companies the server says the user may use (`auth.companies`)
 * and marks the one it resolved (`auth.company`). Picking one is a request,
 * not a client-side switch: the server checks it, remembers it, and answers
 * with a toast; the page reloads with that company's records.
 *
 * Hidden for a user with no usable company — the ERP pages say so themselves.
 */
export function CompanySwitcher() {
    const { companies, company } = usePage().props.auth;

    if (companies.length === 0) {
        return null;
    }

    const select = (id: number) => {
        if (id === company?.id) {
            return;
        }

        router.put(update.url(), { company_id: id }, { preserveScroll: true });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className="gap-2"
                    aria-label="Switch company"
                    data-test="company-switcher"
                >
                    <Building2 className="size-4" />
                    <span className="max-w-40 truncate">
                        {company === null ? 'Select a company' : company.name}
                    </span>
                    {company !== null && (
                        <span className="badge hidden badge-ghost font-mono badge-sm sm:inline-flex">
                            {company.short_code}
                        </span>
                    )}
                    <ChevronsUpDown className="size-3.5 opacity-60" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent className="min-w-64" align="end">
                <DropdownMenuLabel>Working in</DropdownMenuLabel>
                <DropdownMenuSeparator />

                {companies.map((option) => (
                    <DropdownMenuItem
                        key={option.id}
                        onClick={() => select(option.id)}
                        data-test={`select-company-${option.short_code}`}
                    >
                        <Check
                            className={
                                option.id === company?.id
                                    ? 'size-4'
                                    : 'size-4 opacity-0'
                            }
                        />
                        <span className="flex-1 truncate">{option.name}</span>
                        <span className="font-mono text-xs text-base-content/60">
                            {option.short_code}
                        </span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
