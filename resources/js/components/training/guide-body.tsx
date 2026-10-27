import type { ComponentType, LazyExoticComponent } from 'react';
import { lazy, Suspense } from 'react';
import { useTrainingLanguage } from '@/components/training/training-language';
import { Callout, GuideProvider, T } from '@/components/training/vocabulary';
import type { TrainingPage, TrainingTocSection } from '@/types';

/**
 * Renders one guide — the same body on the manual page and in the help dialog.
 *
 * Guides are split out per file and loaded only when shown, so no screen's
 * bundle carries the manual. Each lazy component is made once, here at module
 * level, keyed by its guide path (`admin/users`): one made
 * during render would remount on every render.
 */
const guides: Record<
    string,
    LazyExoticComponent<ComponentType>
> = Object.fromEntries(
    Object.entries(
        import.meta.glob<{ default: ComponentType }>('./guides/**/*.tsx'),
    ).map(([path, loader]) => [
        path.slice('./guides/'.length, -'.tsx'.length),
        lazy(loader),
    ]),
);

export function GuideBody({
    page,
    sections,
}: {
    page: TrainingPage;
    sections: TrainingTocSection[];
}) {
    const Guide = page.status === 'written' ? guides[page.guide] : undefined;

    if (!Guide) {
        return <NotDocumented />;
    }

    return (
        <GuideProvider page={page} sections={sections}>
            <Suspense fallback={<GuideSkeleton />}>
                <Guide />
            </Suspense>
        </GuideProvider>
    );
}

export function NotDocumented() {
    return (
        <div data-test="training-not-documented">
            <Callout
                tone="info"
                en="This screen has not been documented yet. Its guide is on the list and will appear here once it is written."
                bn="এই স্ক্রিনের গাইড এখনো লেখা হয়নি। এটি তালিকায় আছে — লেখা হলেই এখানে দেখা যাবে।"
            />
        </div>
    );
}

/**
 * The one review statement for the whole manual — there are no per-page
 * "reviewed" flags, because such a flag goes stale the moment a guide changes.
 */
export function TrainingDisclaimer() {
    return (
        <p className="text-xs text-base-content/60">
            <T
                en="These guides describe the screens as they were when written, using made-up people and records. If a screen behaves differently from its guide, the screen is right — please tell your administrator so the guide can be corrected."
                bn="গাইডগুলো লেখার সময় স্ক্রিন যেমন ছিল, তেমনই বর্ণনা করা হয়েছে; উদাহরণের ব্যক্তি ও রেকর্ডগুলো কাল্পনিক। কোনো স্ক্রিন গাইডের চেয়ে ভিন্নভাবে কাজ করলে স্ক্রিনটিই সঠিক — গাইডটি ঠিক করার জন্য আপনার অ্যাডমিনিস্ট্রেটরকে জানান।"
            />
        </p>
    );
}

/** Shown while a guide's file loads. */
function GuideSkeleton() {
    const language = useTrainingLanguage();

    return (
        <div
            className="flex animate-pulse flex-col gap-3"
            aria-busy="true"
            aria-label={language === 'bn' ? 'লোড হচ্ছে' : 'Loading'}
        >
            <div className="h-5 w-1/3 rounded bg-base-300" />
            <div className="h-4 w-full rounded bg-base-300" />
            <div className="h-4 w-5/6 rounded bg-base-300" />
            <div className="h-40 w-full rounded bg-base-300" />
        </div>
    );
}
