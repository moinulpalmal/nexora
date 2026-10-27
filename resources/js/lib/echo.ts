import { usePage } from '@inertiajs/react';
import { configureEcho, echo } from '@laravel/echo-react';
import type { ChannelAuthorizationCallback } from 'pusher-js';
import { useEffect, useRef } from 'react';

/**
 * Live push over Reverb — configured lazily, from the `broadcasting` shared prop.
 *
 * The connection details come from the server at runtime (not `VITE_REVERB_*`
 * baked into the bundle), so moving Reverb to another host or port is a config
 * change, not a rebuild. `null` (a guest, or no Reverb) means nothing connects
 * and every hook here is a no-op — the bell still updates on the next visit.
 */

type Broadcasting = { key: string; host: string; port: number; scheme: string };

let configuredFor: string | null = null;

/** The XSRF cookie Laravel sets, for the channel-authorisation POST. */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match === null ? '' : decodeURIComponent(match[1]);
}

function connect(settings: Broadcasting): void {
    const signature = `${settings.key}@${settings.scheme}://${settings.host}:${settings.port}`;

    if (configuredFor === signature) {
        return;
    }

    const secure = settings.scheme === 'https';

    configureEcho({
        broadcaster: 'reverb',
        key: settings.key,
        wsHost: settings.host,
        wsPort: settings.port,
        wssPort: settings.port,
        forceTLS: secure,
        enabledTransports: ['ws', 'wss'],
        // A plain `fetch`: Inertia v3 ships no Axios for Echo to borrow.
        authorizer: (channel: { name: string }) => ({
            authorize: (
                socketId: string,
                callback: ChannelAuthorizationCallback,
            ) => {
                fetch('/broadcasting/auth', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': xsrfToken(),
                    },
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name,
                    }),
                })
                    .then((response) =>
                        response.ok
                            ? response.json()
                            : Promise.reject(
                                  new Error(String(response.status)),
                              ),
                    )
                    .then((data: { auth: string }) => callback(null, data))
                    .catch((error: Error) => callback(error, null));
            },
        }),
    });

    configuredFor = signature;
}

function useBroadcasting(): Broadcasting | null {
    return usePage().props.broadcasting;
}

/**
 * Listen for database notifications pushed to the signed-in user's private
 * channel (`App.Models.User.{id}`, see routes/channels.php).
 */
export function useUserNotifications(
    userId: number | null,
    onNotification: (payload: unknown) => void,
): void {
    const settings = useBroadcasting();
    const handler = useRef(onNotification);
    const signature = settings === null ? null : JSON.stringify(settings);

    useEffect(() => {
        handler.current = onNotification;
    });

    useEffect(() => {
        if (signature === null || userId === null) {
            return;
        }

        connect(JSON.parse(signature) as Broadcasting);

        const channel = echo().private(`App.Models.User.${userId}`);
        const listener = (payload: unknown) => handler.current(payload);

        channel.notification(listener);

        return () => {
            channel.stopListeningForNotification(listener);
        };
    }, [signature, userId]);
}
