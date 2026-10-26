import { Head, router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import MailPanel from '@/components/admin/application-settings/mail-panel';
import QuickLoginPanel from '@/components/admin/application-settings/quick-login-panel';
import Heading from '@/components/heading';
import { edit } from '@/routes/admin/application-settings';
import type { StatusOption } from '@/types';

type MailSection = {
    transport: string;
    host: string | null;
    port: number | null;
    encryption: string;
    username: string | null;
    passwordSet: boolean;
    passwordUnreadable: boolean;
    from_address: string | null;
    from_name: string | null;
    complete: boolean;
    transports: StatusOption[];
    encryptions: StatusOption[];
    testRecipient: string | null;
    updatedBy: string | null;
    updatedAt: string | null;
};

type QuickLoginSection = {
    enabled: boolean;
    environmentAllows: boolean;
    environment: string;
    accounts: { label: string; username: string; exists: boolean }[];
    updatedBy: string | null;
    updatedAt: string | null;
};

type TabKey = 'mail' | 'quick-login';

type Props = {
    sections: {
        mail: MailSection | null;
        'quick-login': QuickLoginSection | null;
    };
};

/**
 * The application-setup hub — one URL, one tab per application-level setting
 * (ARCHITECTURE.md §5). A flat page: there is no collection behind it.
 *
 * **One route, `?tab=` in the query string, client-side switching.** Every
 * section the user may view arrives in this one response, so switching tabs
 * only replaces the URL (`only: []`) — and a save's `back()` or a bookmark
 * lands on the tab in use rather than the first one.
 *
 * **A tab whose section is `null` is not rendered**: the server sends `null`
 * rather than omitting the key, so "no permission" is never mistaken for "not
 * loaded".
 */
export default function ApplicationSettings({ sections }: Props) {
    const { url } = usePage();

    const tabs = useMemo(
        () =>
            (
                [
                    sections.mail && { key: 'mail' as const, label: 'Mail Server' },
                    sections['quick-login'] && {
                        key: 'quick-login' as const,
                        label: 'Quick Login',
                    },
                ] as const
            ).filter((tab): tab is { key: TabKey; label: string } =>
                Boolean(tab),
            ),
        [sections],
    );

    const requestedTab = new URLSearchParams(url.split('?')[1] ?? '').get('tab');
    const activeTab =
        tabs.find((tab) => tab.key === requestedTab)?.key ?? tabs[0]?.key;

    const selectTab = (tab: string) => {
        router.visit(edit.url({ query: { tab } }), {
            only: [],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Application Settings" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Application Settings"
                    description="Software-level setup: how mail goes out, and the development quick login."
                />

                {tabs.length === 0 ? (
                    <p className="text-sm text-base-content/60">
                        You do not have access to any application setting.
                    </p>
                ) : (
                    <>
                        <div role="tablist" className="tabs tabs-box w-fit">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.key}
                                    type="button"
                                    role="tab"
                                    className={`tab ${activeTab === tab.key ? 'tab-active' : ''}`}
                                    onClick={() => selectTab(tab.key)}
                                    data-test={`application-settings-tab-${tab.key}`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>

                        {activeTab === 'mail' && sections.mail && (
                            <MailPanel {...sections.mail} />
                        )}

                        {activeTab === 'quick-login' && sections['quick-login'] && (
                            <QuickLoginPanel {...sections['quick-login']} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

ApplicationSettings.layout = {
    breadcrumbs: [{ title: 'Application Settings', href: edit() }],
};
