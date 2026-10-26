import { useEffect, useState } from 'react';
import { availability } from '@/routes/admin/users';

export type AvailabilityState = 'idle' | 'checking' | 'available' | 'taken';

/** Below this, the value is too short to be worth asking the server about. */
const MINIMUM_LENGTH = 3;

const DEBOUNCE_MS = 400;

/**
 * Ask the server whether a username or email is still free, as it is typed.
 *
 * Deleted users' values count as taken — the unique indexes include them. A
 * convenience only: `UserStoreRequest` / `UserUpdateRequest` enforce
 * uniqueness, and a failed check never blocks the form.
 *
 * A plain `fetch` rather than an Inertia visit: this is a read, not a form
 * submission, so there is no page state to own (ARCHITECTURE.md §8.4).
 *
 * @param ignore The id of the user being edited, so their own value reads free.
 */
export function useAvailability(
    field: 'username' | 'email',
    value: string,
    ignore?: number,
): AvailabilityState {
    const trimmed = value.trim().toLowerCase();
    const tooShort = trimmed.length < MINIMUM_LENGTH;

    // Keyed by the value it describes, so a result for a stale keystroke is
    // simply not matched below rather than having to be cancelled.
    const [result, setResult] = useState<{
        value: string;
        available: boolean;
    } | null>(null);

    useEffect(() => {
        if (tooShort) {
            return;
        }

        const controller = new AbortController();

        const timer = setTimeout(() => {
            fetch(
                availability.url({
                    query: { field, value: trimmed, ignore },
                }),
                {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                        // Without this, StartSession records this URL as the
                        // session's "previous URL", and a later `back()` (a
                        // validation failure, a guard refusal) would land the
                        // user on this JSON endpoint.
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            )
                .then((response) =>
                    response.ok ? response.json() : Promise.reject(response),
                )
                .then((body: { available?: boolean }) =>
                    setResult({
                        value: trimmed,
                        available: body.available === true,
                    }),
                )
                // A failed check must not block the form; the server decides.
                .catch(() => undefined);
        }, DEBOUNCE_MS);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [field, trimmed, ignore, tooShort]);

    if (tooShort) {
        return 'idle';
    }

    if (result?.value === trimmed) {
        return result.available ? 'available' : 'taken';
    }

    return 'checking';
}
