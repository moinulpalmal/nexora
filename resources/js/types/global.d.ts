import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            /** `['*']` straight after sign-in means "collapse every group". */
            collapsedNavGroups: string[];
            /** The stored `App\Enums\Theme` value for this request. */
            theme: string;
            [key: string]: unknown;
        };
    }
}
