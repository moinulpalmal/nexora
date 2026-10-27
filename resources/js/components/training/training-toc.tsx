import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { useTrainingLanguage } from '@/components/training/training-language';
import { T } from '@/components/training/vocabulary';
import { cn } from '@/lib/utils';
import { show } from '@/routes/training';
import type { TrainingTocSection } from '@/types';

/**
 * The manual's table of contents, by section.
 *
 * Search matches either language's title and the key, whichever language is
 * showing, so a reader reading Bangla can still find "Users". **A section's
 * visibility is decided from the match test, never from what is rendered** —
 * a manual that hid sections by counting visible children could never bring a
 * hidden section back (found in compozit's predecessor).
 */
export function TrainingToc({
    sections,
    search = '',
    current,
    compact = false,
}: {
    sections: TrainingTocSection[];
    search?: string;
    current?: string;
    compact?: boolean;
}) {
    const language = useTrainingLanguage();
    const needle = search.trim().toLocaleLowerCase();

    /*
     * Opening a guide unfolds its group, and that counts as unfolding it — the
     * main sidebar's rule (ARCHITECTURE.md §8.3). It can still be folded again.
     * Adjusted during render when the guide's group changes, React's pattern
     * for state that follows a prop, rather than in an effect.
     */
    const currentSection = sections.find((section) =>
        section.pages.some((page) => page.key === current),
    )?.key;
    const [collapsed, setCollapsed] = useState<string[]>(() =>
        readCollapsed().filter((key) => key !== currentSection),
    );
    const [openedFor, setOpenedFor] = useState(currentSection);

    if (openedFor !== currentSection) {
        setOpenedFor(currentSection);

        if (
            currentSection !== undefined &&
            collapsed.includes(currentSection)
        ) {
            setCollapsed(collapsed.filter((key) => key !== currentSection));
        }
    }

    const visible = sections
        .map((section) => ({
            ...section,
            pages: section.pages.filter(
                (page) =>
                    needle === '' ||
                    [page.title_en, page.title_bn, page.key].some((text) =>
                        text.toLocaleLowerCase().includes(needle),
                    ),
            ),
        }))
        .filter((section) => section.pages.length > 0);

    /*
     * The compact list beside a guide folds by group, like the main sidebar
     * (owner, 2026-10-27), remembered in this browser. A search unfolds
     * everything it matched.
     */
    const isOpen = (section: TrainingTocSection): boolean =>
        !compact || needle !== '' || !collapsed.includes(section.key);

    const toggle = (key: string): void => {
        const next = collapsed.includes(key)
            ? collapsed.filter((item) => item !== key)
            : [...collapsed, key];

        setCollapsed(next);
        writeCollapsed(next);
    };

    if (visible.length === 0) {
        return (
            <p
                className="text-sm text-base-content/60"
                data-test="training-toc-empty"
            >
                <T en="No guide matches." bn="কোনো গাইড মেলেনি।" />
            </p>
        );
    }

    return (
        <nav
            data-test="training-toc"
            className={cn(
                'grid',
                compact ? 'gap-3' : 'gap-6 md:grid-cols-2 xl:grid-cols-3',
            )}
        >
            {visible.map((section) => {
                const open = isOpen(section);
                const label = language === 'bn' ? section.bn : section.en;

                return (
                    <section
                        key={section.key}
                        data-test="training-toc-section"
                        className="flex flex-col gap-2"
                    >
                        {compact ? (
                            <button
                                type="button"
                                onClick={() => toggle(section.key)}
                                aria-expanded={open}
                                data-test="training-toc-toggle"
                                className="flex w-full items-center justify-between rounded-field px-2 py-1 text-xs font-medium text-base-content/70 hover:bg-base-200"
                            >
                                <span>{label}</span>
                                <ChevronRight
                                    className={cn(
                                        'size-4 transition-transform',
                                        open && 'rotate-90',
                                    )}
                                />
                            </button>
                        ) : (
                            <h3 className="text-xs font-semibold tracking-wide text-base-content/70 uppercase">
                                {label}
                            </h3>
                        )}
                        {open ? (
                            <ul className="flex flex-col gap-1">
                                {section.pages.map((page) => (
                                    <li
                                        key={page.key}
                                        data-test="training-toc-item"
                                    >
                                        <Link
                                            href={show(page.key).url}
                                            className={cn(
                                                'flex items-center justify-between gap-2 rounded-field px-2 py-1 text-sm hover:bg-base-200',
                                                current === page.key &&
                                                    'bg-base-200 font-semibold',
                                            )}
                                        >
                                            <span>
                                                {language === 'bn'
                                                    ? page.title_bn
                                                    : page.title_en}
                                            </span>
                                            {page.status === 'pending' ? (
                                                <span className="badge shrink-0 badge-ghost badge-sm">
                                                    <T
                                                        en="Coming soon"
                                                        bn="শীঘ্রই আসছে"
                                                    />
                                                </span>
                                            ) : null}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        ) : null}
                    </section>
                );
            })}
        </nav>
    );
}

/** Folded groups are remembered in this browser only; storage may be blocked. */
const COLLAPSED_KEY = 'training.toc.collapsed';

function readCollapsed(): string[] {
    try {
        const stored = JSON.parse(
            window.localStorage.getItem(COLLAPSED_KEY) ?? '[]',
        );

        return Array.isArray(stored) ? stored.map(String) : [];
    } catch {
        return [];
    }
}

function writeCollapsed(keys: string[]): void {
    try {
        window.localStorage.setItem(COLLAPSED_KEY, JSON.stringify(keys));
    } catch {
        // Storage is blocked; the folding still works for this visit.
    }
}
