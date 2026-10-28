import type { ReactNode } from 'react';
import type { ModuleHelp, PermissionHelp } from '@/types';

/**
 * The body of a permission's hover card: its full name, what it allows, any
 * caveat, and where in the application it applies. The copy comes from
 * `PermissionDescriptions` on the server.
 */
export function PermissionHelpCard({
    name,
    help,
}: {
    name: string;
    help: PermissionHelp;
}) {
    return (
        <div className="grid gap-1.5">
            <p className="flex items-center gap-2 font-mono text-xs font-semibold">
                {name}
                {!help.curated && (
                    <span className="badge badge-ghost font-sans badge-xs">
                        uncatalogued
                    </span>
                )}
            </p>

            <HelpLine label="Allows">{help.summary}</HelpLine>

            {help.note && <HelpLine label="Note">{help.note}</HelpLine>}

            {help.where && <HelpLine label="Where">{help.where}</HelpLine>}
        </div>
    );
}

/**
 * The body of a module heading's hover card.
 */
export function ModuleHelpCard({ help }: { help: ModuleHelp }) {
    return (
        <div className="grid gap-1.5">
            <p className="text-sm font-semibold">{help.label}</p>

            <p className="text-base-content/80">{help.summary}</p>

            {help.where && <HelpLine label="Where">{help.where}</HelpLine>}
        </div>
    );
}

function HelpLine({ label, children }: { label: string; children: ReactNode }) {
    return (
        <p className="text-base-content/80">
            <span className="font-medium text-base-content/60">{label}: </span>
            {children}
        </p>
    );
}
