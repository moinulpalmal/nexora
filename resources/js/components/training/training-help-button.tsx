import { Link, usePage } from '@inertiajs/react';
import { CircleHelp, ExternalLink } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    GuideBody,
    TrainingDisclaimer,
} from '@/components/training/guide-body';
import {
    LanguageToggle,
    useTrainingLanguage,
} from '@/components/training/training-language';
import { T } from '@/components/training/vocabulary';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { panel, show } from '@/routes/training';
import type { TrainingPage, TrainingScreen, TrainingTocSection } from '@/types';

/**
 * The header's **?** — the guide to the screen in front of the user, in a modal
 * over it (owner, 2026-10-27). Absent on a screen no guide covers.
 *
 * The guide is fetched when the dialog opens, so no screen pays for it until
 * someone asks. Like every modal here it never light-dismisses (§8.7): the ✕
 * is the way out.
 */
export function TrainingHelpButton() {
    const { training } = usePage().props;
    const language = useTrainingLanguage();

    if (!training) {
        return null;
    }

    const label =
        language === 'bn' ? 'এই স্ক্রিনের গাইড' : 'Guide to this screen';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label={label}
                    title={label}
                    data-test="training-help"
                >
                    <CircleHelp className="size-5" />
                </Button>
            </DialogTrigger>

            <DialogContent className="max-w-4xl" scrollBody>
                <DialogTitle data-test="training-dialog-title">
                    <T en={training.title_en} bn={training.title_bn} />
                </DialogTitle>
                <DialogDescription>
                    <T
                        en="How to use this screen, step by step."
                        bn="এই স্ক্রিনটি কীভাবে ব্যবহার করবেন, ধাপে ধাপে।"
                    />
                </DialogDescription>

                {/* `scrollBody` gives the third child the height budget; the
                    toolbar stays put above the part that scrolls. */}
                <div className="grid min-h-0 grid-rows-[auto_minmax(0,1fr)] gap-4">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <LanguageToggle />
                        <Link
                            href={show(training.key).url}
                            className="inline-flex link items-center gap-1 text-sm link-primary"
                            data-test="training-open-full"
                        >
                            <T
                                en="Open the full guide"
                                bn="পুরো গাইডটি খুলুন"
                            />
                            <ExternalLink className="size-3.5" />
                        </Link>
                    </div>

                    <div
                        className="grid min-h-0 content-start gap-6 overflow-y-auto pr-1"
                        data-test="dialog-scroll-body"
                    >
                        <HelpPanel screen={training} />
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

type Loaded = { page: TrainingPage; sections: TrainingTocSection[] };

/**
 * The fetched guide. Mounted only while the dialog is open (a closed dialog
 * holds no state), so every opening reads it afresh.
 */
function HelpPanel({ screen }: { screen: TrainingScreen }) {
    const [loaded, setLoaded] = useState<Loaded | 'failed' | null>(null);

    useEffect(() => {
        // A plain read, not a visit (ARCHITECTURE.md §8.4); the header keeps
        // `StartSession` from recording the JSON URL as the "previous URL".
        const controller = new AbortController();

        fetch(panel(screen.key).url, {
            signal: controller.signal,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Training panel: ${response.status}`);
                }

                return response.json() as Promise<Loaded>;
            })
            .then((data) => setLoaded(data))
            .catch(() => {
                if (!controller.signal.aborted) {
                    setLoaded('failed');
                }
            });

        return () => controller.abort();
    }, [screen.key]);

    if (loaded === null) {
        return (
            <div className="flex animate-pulse flex-col gap-3" aria-busy="true">
                <div className="h-5 w-1/3 rounded bg-base-300" />
                <div className="h-4 w-full rounded bg-base-300" />
                <div className="h-40 w-full rounded bg-base-300" />
            </div>
        );
    }

    if (loaded === 'failed') {
        return (
            <p className="text-error">
                <T
                    en="The guide could not be loaded. Try again, or open the full guide."
                    bn="গাইডটি লোড করা যায়নি। আবার চেষ্টা করুন, অথবা পুরো গাইডটি খুলুন।"
                />
            </p>
        );
    }

    return (
        <>
            <GuideBody page={loaded.page} sections={loaded.sections} />
            <TrainingDisclaimer />
        </>
    );
}
