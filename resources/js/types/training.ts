/**
 * Training. Shapes of `App\Services\Training\TrainingRegistry`'s output; see
 * documentation/training.md.
 */

/** The two languages every guide is written in. */
export type TrainingLanguage = 'en' | 'bn';

/** `written` once a guide exists; `pending` renders "not yet documented". */
export type TrainingStatus = 'written' | 'pending';

/** The shared `training` prop: the guide to the screen in front of the user. */
export type TrainingScreen = {
    key: string;
    title_en: string;
    title_bn: string;
    status: TrainingStatus;
};

/** One entry of the manual's table of contents. */
export type TrainingTocPage = TrainingScreen;

/** One section of the manual's table of contents, pages in order. */
export type TrainingTocSection = {
    key: string;
    en: string;
    bn: string;
    pages: TrainingTocPage[];
};

/** A screenshot: what it shows, and its URL — `null` until it is captured. */
export type TrainingShot = {
    description: string;
    url: string | null;
};

/** Everything one guide needs to render — `TrainingRegistry::present()`. */
export type TrainingPage = TrainingScreen & {
    guide: string;
    section_en: string;
    section_bn: string;
    shots: Record<string, TrainingShot>;
    /** The live screen, when it takes no parameters to open. */
    screen_url: string | null;
};
