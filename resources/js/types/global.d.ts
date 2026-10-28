import type { Auth, PasswordPolicy } from '@/types/auth';
import type { TrainingScreen } from '@/types/training';

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
            /** Uploaded images from Application Settings → Branding; `null` means the default. */
            branding: { logo: string | null; favicon: string | null };
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
            /** The guide to this screen, for the header's **?** — `null` when none covers it. */
            training: TrainingScreen | null;
            [key: string]: unknown;
        };
    }
}
