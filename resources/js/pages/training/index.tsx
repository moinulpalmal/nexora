import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { TrainingDisclaimer } from '@/components/training/guide-body';
import {
    LanguageToggle,
    useTrainingLanguage,
} from '@/components/training/training-language';
import { TrainingToc } from '@/components/training/training-toc';
import { T } from '@/components/training/vocabulary';
import { index } from '@/routes/training';
import type { TrainingTocSection } from '@/types';

type Props = {
    sections: TrainingTocSection[];
};

/**
 * The training manual — see documentation/training.md. Every screen
 * of the application, by section, in English and Bangla. Open to every
 * signed-in user.
 */
export default function TrainingIndex({ sections }: Props) {
    const language = useTrainingLanguage();
    const [search, setSearch] = useState('');

    const written = sections
        .flatMap((section) => section.pages)
        .filter((page) => page.status === 'written').length;
    const total = sections.flatMap((section) => section.pages).length;

    return (
        <>
            <Head title="Training" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-0.5">
                        <h2 className="text-xl font-semibold tracking-tight">
                            <T en="Training" bn="প্রশিক্ষণ" />
                        </h2>
                        <p className="text-sm text-base-content/60">
                            <T
                                en={`How to use every screen, step by step. ${written} of ${total} screens have a guide so far.`}
                                bn={`প্রতিটি স্ক্রিন কীভাবে ব্যবহার করবেন, ধাপে ধাপে। এখন পর্যন্ত ${total}টি স্ক্রিনের মধ্যে ${written}টির গাইড আছে।`}
                            />
                        </p>
                    </div>
                    <LanguageToggle />
                </header>

                <label className="input w-full max-w-md">
                    <span className="sr-only">
                        {language === 'bn' ? 'গাইড খুঁজুন' : 'Search guides'}
                    </span>
                    <input
                        type="search"
                        data-test="training-search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={
                            language === 'bn'
                                ? 'গাইড খুঁজুন — বাংলা বা ইংরেজিতে'
                                : 'Search guides — in English or Bangla'
                        }
                    />
                </label>

                <TrainingToc sections={sections} search={search} />

                <TrainingDisclaimer />
            </div>
        </>
    );
}

TrainingIndex.layout = {
    breadcrumbs: [{ title: 'Training', href: index() }],
};
