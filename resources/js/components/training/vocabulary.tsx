import { Link } from '@inertiajs/react';
import { CircleAlert, Info, TriangleAlert } from 'lucide-react';
import type { ReactNode } from 'react';
import { createContext, use } from 'react';
import { useTrainingLanguage } from '@/components/training/training-language';
import { cn } from '@/lib/utils';
import { show } from '@/routes/training';
import type { TrainingPage, TrainingTocSection } from '@/types';

/**
 * The training content vocabulary — see documentation/training.md §4.
 *
 * A guide is assembled from these elements and nothing else, so a change to how
 * guides look is one edit here rather than one per guide. **Every element that
 * carries words takes both `en` and `bn`**, required: a guide written in one
 * language does not type-check. The Bangla is its own explanation in plain
 * Bangla, not a translation of the English; words the screen itself shows in
 * English — button labels, column names, permission names — stay in English inside it so the reader
 * can match what they read to what they see.
 */

/** Text in both languages. Inline markup (`<Ui>`, `<b>`) is allowed in each. */
export type Bilingual = { en: ReactNode; bn: ReactNode };

type GuideContextValue = {
    page: TrainingPage;
    sections: TrainingTocSection[];
};

const GuideContext = createContext<GuideContextValue | null>(null);

function useGuide(): GuideContextValue {
    const context = use(GuideContext);

    if (!context) {
        throw new Error(
            'Training elements must be rendered inside <GuideProvider>.',
        );
    }

    return context;
}

/**
 * What every element reads: the page being shown (for its screenshots) and the
 * table of contents (for related guides' titles).
 */
export function GuideProvider({
    page,
    sections,
    children,
}: GuideContextValue & { children: ReactNode }) {
    return <GuideContext value={{ page, sections }}>{children}</GuideContext>;
}

/** The active language's half of a pair. */
export function T({ en, bn }: Bilingual) {
    const language = useTrainingLanguage();

    return <>{language === 'bn' ? bn : en}</>;
}

/** A label exactly as the screen shows it — a button, a column, a status. */
export function Ui({ children }: { children: ReactNode }) {
    return (
        <span className="rounded bg-base-200 px-1 py-0.5 font-medium whitespace-nowrap text-base-content">
            {children}
        </span>
    );
}

/**
 * The fixed shape of every guide (owner, 2026-10-27). Sections may be left
 * out when there is nothing to say, never reordered — `training:gaps` checks
 * the order.
 */
const SECTION_HEADINGS = {
    purpose: { en: 'What this screen is for', bn: 'এই স্ক্রিনটি কী কাজে লাগে' },
    who: {
        en: 'Who uses it, and the permission it needs',
        bn: 'কারা ব্যবহার করেন, কোন পারমিশন লাগে',
    },
    before: { en: 'Before you start', bn: 'শুরু করার আগে' },
    steps: { en: 'Step by step', bn: 'ধাপে ধাপে' },
    fields: { en: 'Fields and options', bn: 'ফিল্ড ও অপশন' },
    messages: { en: 'Messages you may see', bn: 'যেসব বার্তা দেখতে পারেন' },
    mistakes: { en: 'Common mistakes', bn: 'সাধারণ ভুল' },
    related: { en: 'Related guides', bn: 'সম্পর্কিত গাইড' },
} as const;

export type GuideSectionKind = keyof typeof SECTION_HEADINGS;

export function Guide({ children }: { children: ReactNode }) {
    const language = useTrainingLanguage();

    return (
        <article
            lang={language}
            data-test="training-guide"
            className="flex flex-col gap-6 text-sm leading-relaxed"
        >
            {children}
        </article>
    );
}

export function GuideSection({
    kind,
    children,
}: {
    kind: GuideSectionKind;
    children: ReactNode;
}) {
    return (
        <section
            data-test={`training-section-${kind}`}
            className="flex flex-col gap-3"
        >
            <h2 className="border-b border-base-300 pb-1 text-base font-semibold">
                <T {...SECTION_HEADINGS[kind]} />
            </h2>
            {children}
        </section>
    );
}

/** A sub-heading inside a section, e.g. one per dialog the steps open. */
export function SubHeading({ en, bn }: Bilingual) {
    return (
        <h3 className="mt-2 font-semibold">
            <T en={en} bn={bn} />
        </h3>
    );
}

export function Para({ en, bn }: Bilingual) {
    return (
        <p>
            <T en={en} bn={bn} />
        </p>
    );
}

/** A bulleted list; its items are `<Bullet>`. */
export function Bullets({ children }: { children: ReactNode }) {
    return <ul className="ml-5 flex list-disc flex-col gap-1.5">{children}</ul>;
}

export function Bullet({ en, bn }: Bilingual) {
    return (
        <li>
            <T en={en} bn={bn} />
        </li>
    );
}

/** Numbered steps; its items are `<Step>`. A step may hold a `<Shot>` below it. */
export function Steps({ children }: { children: ReactNode }) {
    return (
        <ol className="ml-5 flex list-decimal flex-col gap-3 marker:font-semibold">
            {children}
        </ol>
    );
}

export function Step({
    en,
    bn,
    children,
}: Bilingual & { children?: ReactNode }) {
    return (
        <li className="pl-1">
            <T en={en} bn={bn} />
            {children ? <div className="mt-3">{children}</div> : null}
        </li>
    );
}

/** A list of `<Field>`s. */
export function Fields({ children }: { children: ReactNode }) {
    return <dl className="flex flex-col gap-3">{children}</dl>;
}

/**
 * One input, filter or column. `label` is what the screen shows, in English in
 * both languages, so the reader can find it.
 */
export function Field({
    label,
    en,
    bn,
    children,
}: Bilingual & { label: string; children?: ReactNode }) {
    return (
        <div className="flex flex-col gap-1">
            <dt>
                <Ui>{label}</Ui>
            </dt>
            <dd className="ml-1">
                <T en={en} bn={bn} />
                {children ? <div className="mt-2">{children}</div> : null}
            </dd>
        </div>
    );
}

/** A message the screen shows, quoted exactly, and what it means. */
export function Message({ text, en, bn }: Bilingual & { text: string }) {
    return (
        <div className="flex flex-col gap-1 rounded-box border border-base-300 p-3">
            <q className="font-medium italic">{text}</q>
            <span>
                <T en={en} bn={bn} />
            </span>
        </div>
    );
}

const CALLOUT_TONES = {
    info: { className: 'alert-info', icon: Info },
    warn: { className: 'alert-warning', icon: TriangleAlert },
    danger: { className: 'alert-error', icon: CircleAlert },
} as const;

/**
 * `danger` is reserved for a mistake **nothing on screen catches** — the save
 * goes through and the wrong value just sits there. Overusing it destroys the
 * signal; a mistake the screen refuses with a message is `warn`.
 */
export function Callout({
    tone,
    en,
    bn,
}: Bilingual & { tone: keyof typeof CALLOUT_TONES }) {
    const { className, icon: Icon } = CALLOUT_TONES[tone];

    return (
        <div
            role="note"
            data-test={`training-callout-${tone}`}
            className={cn('alert items-start alert-soft', className)}
        >
            <Icon className="mt-0.5 size-5 shrink-0" />
            <div>
                <T en={en} bn={bn} />
            </div>
        </div>
    );
}

/**
 * A screenshot of the screen, captured by `composer training:screens` against
 * fictional data. `id` must be declared on the page's registry entry;
 * `training:gaps` checks both ways.
 */
export function Shot({ id, en, bn }: Bilingual & { id: string }) {
    const { page } = useGuide();
    const shot = page.shots[id];

    return (
        <figure
            data-test={`training-shot-${id}`}
            className="flex flex-col gap-2"
        >
            {shot?.url ? (
                <a href={shot.url} target="_blank" rel="noreferrer">
                    <img
                        src={shot.url}
                        alt={shot.description}
                        loading="lazy"
                        className="w-full rounded-box border border-base-300 shadow-sm"
                    />
                </a>
            ) : (
                <div className="flex h-32 items-center justify-center rounded-box border border-dashed border-base-300 text-sm text-base-content/60">
                    <T
                        en="This screenshot has not been captured yet."
                        bn="এই স্ক্রিনশটটি এখনো তোলা হয়নি।"
                    />
                </div>
            )}
            <figcaption className="text-xs text-base-content/70">
                <T en={en} bn={bn} />
            </figcaption>
        </figure>
    );
}

/**
 * Every option of a dropdown, read from `lib/enums.ts` — the same labels the
 * dropdown shows, so the table can never list an option the screen lacks.
 * `explain` must cover every value: a new option fails the type check here
 * until the guide says what it means.
 */
export function EnumOptions<K extends string>({
    labels,
    explain,
}: {
    labels: Record<K, string>;
    explain: Record<K, Bilingual>;
}) {
    const values = Object.keys(labels) as K[];

    return (
        <div className="overflow-x-auto rounded-box border border-base-300">
            <table className="table table-sm">
                <tbody>
                    {values.map((value) => (
                        <tr key={value}>
                            <td className="w-1/3 align-top">
                                <Ui>{labels[value]}</Ui>
                            </td>
                            <td>
                                <T {...explain[value]} />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

/**
 * A link to another guide, and why the reader would go there. `to` must be a
 * registered key; `training:gaps` checks it.
 */
export function RelatedGuide({ to, en, bn }: Bilingual & { to: string }) {
    const { sections } = useGuide();
    const language = useTrainingLanguage();
    const target = sections
        .flatMap((section) => section.pages)
        .find((page) => page.key === to);

    return (
        <div className="flex flex-col">
            <Link
                href={show(to).url}
                className="link font-medium link-primary"
                data-test="training-related"
            >
                {target
                    ? language === 'bn'
                        ? target.title_bn
                        : target.title_en
                    : to}
            </Link>
            <span className="text-sm text-base-content/70">
                <T en={en} bn={bn} />
            </span>
        </div>
    );
}
