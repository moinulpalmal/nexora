import { usePage } from '@inertiajs/react';
import type { SVGAttributes } from 'react';

/*
 * The brand artwork. An administrator's upload (Application Settings →
 * Branding, the shared `branding.logo`) wins; without one, the placeholder
 * mark below is drawn in daisyUI's `primary` / `primary-content`, so it follows
 * the active theme rather than carrying fixed colours.
 */

const MARK_PATH = 'M10 34V6h5.2l11.6 17.6V6H32v28h-5.2L15.2 16.4V34Z';

/**
 * The display-size logo (auth screens). Mark only: the auth layout prints the
 * app name beneath it, so a wordmark here would repeat it. Size it by height
 * (`h-16 w-auto`).
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return <AppLogoMark {...props} />;
}

/**
 * The mark on its own, for sidebar and header chips where the app name sits
 * beside it.
 */
export function AppLogoMark(props: SVGAttributes<SVGElement>) {
    const { branding, name } = usePage().props;

    if (branding.logo !== null) {
        return (
            <img
                src={branding.logo}
                alt={name}
                className={props.className}
                style={{ objectFit: 'contain' }}
                data-test="app-logo-image"
            />
        );
    }

    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            fill="none"
            role="img"
            aria-label="Nexora"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect width="40" height="40" rx="10" className="fill-primary" />
            <path d={MARK_PATH} className="fill-primary-content" />
        </svg>
    );
}
