import { usePage } from '@inertiajs/react';

import { AppLogoMark } from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

export default function AppLogo() {
    const { name, branding } = usePage().props;

    return (
        <>
            {/*
             * The placeholder mark is square; an uploaded logo may be wide, so
             * its chip grows with it (capped) instead of squashing it.
             */}
            <div
                className={cn(
                    'flex h-8 shrink-0 items-center justify-center rounded-md',
                    branding.logo === null ? 'aspect-square' : 'max-w-28',
                )}
            >
                <AppLogoMark className="h-7 w-auto max-w-full" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {name}
                </span>
            </div>
        </>
    );
}
