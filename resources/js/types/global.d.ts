import type { Auth, PasswordPolicy } from '@/types/auth';

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
            passwordPolicy: PasswordPolicy;
            /** The bell's count — `null` for a guest. */
            notifications: { unread_count: number } | null;
            /** Where Echo connects for live push; `null` when there is none. */
            broadcasting: {
                key: string;
                host: string;
                port: number;
                scheme: string;
            } | null;
            [key: string]: unknown;
        };
    }
}
