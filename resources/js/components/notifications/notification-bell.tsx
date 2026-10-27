import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useUserNotifications } from '@/lib/echo';
import {
    index as notificationsIndex,
    read,
    readAll,
} from '@/routes/notifications';

type Item = {
    id: string;
    data: { title?: string; body?: string; url?: string; tone?: string };
    read_at: string | null;
    created_at: string | null;
};

const TONE: Record<string, string> = {
    success: 'bg-success',
    warning: 'bg-warning',
    error: 'bg-error',
    info: 'bg-info',
};

/**
 * The in-app bell.
 *
 * **The count is the shared `notifications` prop**, the server's number on every
 * page, and **the live push only asks for it again** — a pushed notification
 * reloads that one prop rather than incrementing a local counter, so the badge can
 * never drift from what the server holds. With Reverb down, the count still lands
 * on the next visit or refresh.
 *
 * **Generic**: a row draws `title`, `body`, `tone` and `url` from the data, so a
 * module adding a notification adds nothing here. Opening one marks it read and
 * follows its link.
 */
export function NotificationBell() {
    const { auth, notifications } = usePage().props;
    const count = notifications?.unread_count ?? 0;

    const [nonce, setNonce] = useState(0);
    const [items, setItems] = useState<{ nonce: number; data: Item[] } | null>(
        null,
    );

    useUserNotifications(auth.user?.id ?? null, (payload) => {
        router.reload({ only: ['notifications'] });

        // A push that lands while someone is online also pops a toast, so it
        // is seen without opening the bell.
        const pushed = payload as Partial<
            Record<'title' | 'body' | 'url', string>
        >;

        if (pushed.title !== undefined) {
            const url = pushed.url;

            toast.info(pushed.title, {
                description: pushed.body,
                action:
                    url === undefined
                        ? undefined
                        : { label: 'View', onClick: () => router.visit(url) },
            });
        }

        if (items !== null) {
            setNonce((current) => current + 1);
        }
    });

    useEffect(() => {
        if (nonce === 0) {
            return;
        }

        const controller = new AbortController();

        fetch(notificationsIndex.url(), {
            signal: controller.signal,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) =>
                response.ok ? response.json() : Promise.reject(response),
            )
            .then((body: { data: Item[] }) =>
                setItems({ nonce, data: body.data }),
            )
            .catch(() => undefined);

        return () => controller.abort();
    }, [nonce]);

    if (!auth.user) {
        return null;
    }

    const open = (item: Item) => {
        const visit = () => {
            if (item.data.url) {
                router.visit(item.data.url);
            }
        };

        if (item.read_at !== null) {
            visit();

            return;
        }

        router.put(
            read.url(item.id),
            {},
            {
                only: ['notifications'],
                preserveScroll: true,
                preserveState: true,
                onSuccess: visit,
            },
        );
    };

    const markAllRead = () =>
        router.put(
            readAll.url(),
            {},
            {
                only: ['notifications'],
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => setNonce((current) => current + 1),
            },
        );

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    aria-label={
                        count === 0
                            ? 'Notifications'
                            : `Notifications, ${count} unread`
                    }
                    onClick={() => setNonce((current) => current + 1)}
                    data-test="notification-bell"
                >
                    <Bell />
                    {count > 0 && (
                        <span
                            className="absolute -top-0.5 -right-0.5 badge badge-xs tabular-nums badge-error"
                            data-test="notification-count"
                        >
                            {count > 99 ? '99+' : count}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" className="w-80 max-w-[90vw] p-0">
                <div className="flex items-center justify-between border-b border-base-300 px-3 py-2">
                    <span className="text-sm font-semibold">Notifications</span>
                    {count > 0 && (
                        <button
                            type="button"
                            className="link text-xs"
                            onClick={markAllRead}
                            data-test="notifications-read-all"
                        >
                            Mark all read
                        </button>
                    )}
                </div>

                <ul
                    className="max-h-96 overflow-y-auto"
                    data-test="notification-list"
                >
                    {items === null ? (
                        <li className="px-3 py-4 text-sm text-base-content/60">
                            Loading…
                        </li>
                    ) : items.data.length === 0 ? (
                        <li className="px-3 py-4 text-sm text-base-content/60">
                            Nothing yet.
                        </li>
                    ) : (
                        items.data.map((item) => (
                            <li key={item.id}>
                                <button
                                    type="button"
                                    className={`flex w-full gap-2 px-3 py-2 text-left hover:bg-base-200 ${item.read_at === null ? '' : 'opacity-60'}`}
                                    onClick={() => open(item)}
                                    data-test="notification-item"
                                >
                                    <span
                                        className={`mt-1.5 size-2 shrink-0 rounded-full ${item.read_at === null ? (TONE[item.data.tone ?? ''] ?? 'bg-info') : 'bg-transparent'}`}
                                    />
                                    <span className="grid min-w-0 gap-0.5">
                                        <span className="text-sm font-medium">
                                            {item.data.title}
                                        </span>
                                        {item.data.body && (
                                            <span className="text-xs break-words text-base-content/70">
                                                {item.data.body}
                                            </span>
                                        )}
                                        <span className="text-[11px] text-base-content/50">
                                            {item.created_at
                                                ? new Date(
                                                      item.created_at,
                                                  ).toLocaleString()
                                                : ''}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        ))
                    )}
                </ul>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
