import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MonitorPlay } from 'lucide-react';
import {
    GuideBody,
    TrainingDisclaimer,
} from '@/components/training/guide-body';
import {
    LanguageToggle,
    useTrainingLanguage,
} from '@/components/training/training-language';
import { TrainingToc } from '@/components/training/training-toc';
import { T } from '@/components/training/vocabulary';
import { index, show } from '@/routes/training';
import type { TrainingPage, TrainingTocSection } from '@/types';

type Props = {
    page: TrainingPage;
    sections: TrainingTocSection[];
};

/**
 * One guide, linkable and printable — the same body the help dialog shows.
 */
export default function TrainingShow({ page, sections }: Props) {
    const language = useTrainingLanguage();
    const title = language === 'bn' ? page.title_bn : page.title_en;

    return (
        <>
            <Head title={`${page.title_en} — Training`} />

            <div className="flex h-full flex-1 gap-8 p-4">
                <aside className="sticky top-4 hidden max-h-[calc(100vh-6rem)] w-64 shrink-0 self-start overflow-y-auto lg:block print:hidden">
                    <TrainingToc
                        sections={sections}
                        current={page.key}
                        compact
                    />
                </aside>

                <div className="flex min-w-0 flex-1 flex-col gap-6">
                    <header className="flex flex-col gap-3">
                        <Link
                            href={index().url}
                            className="inline-flex link items-center gap-1 text-sm link-hover print:hidden"
                        >
                            <ArrowLeft className="size-4" />
                            <T en="All guides" bn="সব গাইড" />
                        </Link>

                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="space-y-0.5">
                                <p className="text-sm text-base-content/60">
                                    {language === 'bn'
                                        ? page.section_bn
                                        : page.section_en}
                                </p>
                                <h2
                                    className="text-xl font-semibold tracking-tight"
                                    data-test="training-title"
                                >
                                    {title}
                                </h2>
                            </div>

                            <div className="flex flex-wrap items-center gap-2 print:hidden">
                                {page.screen_url ? (
                                    <Link
                                        href={page.screen_url}
                                        className="btn btn-outline btn-sm"
                                        data-test="training-open-screen"
                                    >
                                        <MonitorPlay className="size-4" />
                                        <T
                                            en="Open this screen"
                                            bn="স্ক্রিনটি খুলুন"
                                        />
                                    </Link>
                                ) : null}
                                <LanguageToggle />
                            </div>
                        </div>
                    </header>

                    <div className="max-w-4xl">
                        <GuideBody page={page} sections={sections} />
                    </div>

                    <div className="max-w-4xl">
                        <TrainingDisclaimer />
                    </div>
                </div>
            </div>
        </>
    );
}

TrainingShow.layout = ({ page }: { page: TrainingPage }) => ({
    breadcrumbs: [
        { title: 'Training', href: index() },
        { title: page.title_en, href: show(page.key) },
    ],
});
