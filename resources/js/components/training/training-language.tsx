import { useSyncExternalStore } from 'react';
import { cn } from '@/lib/utils';
import type { TrainingLanguage } from '@/types';

/**
 * Which language the training guides are read in — see
 * documentation/training.md.
 *
 * Held in this browser only (`localStorage['training.lang']`): it follows the
 * reader across guides and reloads, and needs no server state. English until
 * the reader picks Bangla. Storage can be unavailable — a private window,
 * blocked site data — so every access is guarded and a module variable keeps
 * the choice for the rest of the visit.
 *
 * A `useSyncExternalStore`, so the manual page and an open help dialog switch
 * together, and another tab's choice arrives through the `storage` event.
 */
const STORAGE_KEY = 'training.lang';
const CHANGE_EVENT = 'training-language-change';

let fallback: TrainingLanguage = 'en';

function readLanguage(): TrainingLanguage {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        if (stored === 'en' || stored === 'bn') {
            return stored;
        }
    } catch {
        // Storage is blocked; the in-memory choice stands.
    }

    return fallback;
}

function subscribe(onChange: () => void): () => void {
    window.addEventListener('storage', onChange);
    window.addEventListener(CHANGE_EVENT, onChange);

    return () => {
        window.removeEventListener('storage', onChange);
        window.removeEventListener(CHANGE_EVENT, onChange);
    };
}

export function setTrainingLanguage(language: TrainingLanguage): void {
    fallback = language;

    try {
        window.localStorage.setItem(STORAGE_KEY, language);
    } catch {
        // Storage is blocked; the in-memory choice carries this visit.
    }

    window.dispatchEvent(new Event(CHANGE_EVENT));
}

export function useTrainingLanguage(): TrainingLanguage {
    return useSyncExternalStore(subscribe, readLanguage, () => 'en');
}

/**
 * EN / বাংলা. Switches every guide on the page at once.
 */
export function LanguageToggle({ className }: { className?: string }) {
    const language = useTrainingLanguage();

    return (
        <div
            role="group"
            aria-label="Training language"
            className={cn('join', className)}
        >
            <button
                type="button"
                data-test="training-lang-en"
                aria-pressed={language === 'en'}
                className={cn(
                    'btn join-item btn-sm',
                    language === 'en' && 'btn-active btn-primary',
                )}
                onClick={() => setTrainingLanguage('en')}
            >
                EN
            </button>
            <button
                type="button"
                lang="bn"
                data-test="training-lang-bn"
                aria-pressed={language === 'bn'}
                className={cn(
                    'btn join-item btn-sm',
                    language === 'bn' && 'btn-active btn-primary',
                )}
                onClick={() => setTrainingLanguage('bn')}
            >
                বাংলা
            </button>
        </div>
    );
}
