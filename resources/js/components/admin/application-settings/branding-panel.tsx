import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import BrandingSettingController from '@/actions/App/Http/Controllers/Admin/BrandingSettingController';
import { AppLogoMark } from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import ConfirmActionDialog from '@/components/shared/confirm-action-dialog';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';

type Props = {
    /** The uploaded logo, or `null` while the default mark shows. */
    logoUrl: string | null;
    /** The uploaded favicon, or `null` (the tab then uses the logo, or the default icon). */
    faviconUrl: string | null;
    /** Who last changed it, or `null` when it has never been changed. */
    updatedBy: string | null;
    updatedAt: string | null;
};

type Image = 'logo' | 'favicon';

/**
 * The logo and the browser-tab favicon. Each card uploads or removes its own
 * image; raster files only — an uploaded SVG could carry script.
 */
export default function BrandingPanel({
    logoUrl,
    faviconUrl,
    updatedBy,
    updatedAt,
}: Props) {
    return (
        <div className="grid max-w-4xl gap-4">
            <div className="grid gap-4 md:grid-cols-2">
                <ImageCard
                    image="logo"
                    title="Logo"
                    description="Shown in the sidebar beside the application name, and large on the sign-in screens. One image serves every theme, so a transparent background works best."
                    accept=".png,.jpg,.jpeg,.webp"
                    hint="PNG, JPG or WebP, up to 1 MB."
                    url={logoUrl}
                    preview={
                        logoUrl === null ? (
                            <DefaultPreview label="Default mark" />
                        ) : (
                            <img
                                src={logoUrl}
                                alt="Current logo"
                                className="h-16 w-auto max-w-full object-contain"
                            />
                        )
                    }
                />

                <ImageCard
                    image="favicon"
                    title="Favicon"
                    description="The small icon in the browser tab. Without one, the tab uses the logo; without either, the default icon."
                    accept=".png,.ico"
                    hint="A square PNG, or an ICO, up to 256 KB."
                    url={faviconUrl}
                    preview={
                        faviconUrl === null ? (
                            <DefaultPreview
                                label={
                                    logoUrl === null
                                        ? 'Default icon'
                                        : 'Using the logo'
                                }
                            />
                        ) : (
                            <img
                                src={faviconUrl}
                                alt="Current favicon"
                                className="size-12 object-contain"
                            />
                        )
                    }
                />
            </div>

            {updatedBy !== null && updatedAt !== null && (
                <p className="text-xs text-base-content/60">
                    Last changed by {updatedBy} on{' '}
                    {new Date(updatedAt).toLocaleString()}.
                </p>
            )}
        </div>
    );
}

function ImageCard({
    image,
    title,
    description,
    accept,
    hint,
    url,
    preview,
}: {
    image: Image;
    title: string;
    description: string;
    accept: string;
    hint: string;
    url: string | null;
    preview: ReactNode;
}) {
    const canUpdate = useCan('admin.branding.update');

    return (
        <div
            className="card border border-base-300 bg-base-100"
            data-test={`branding-${image}`}
        >
            <div className="card-body gap-4">
                <div>
                    <h2 className="card-title text-base">{title}</h2>
                    <p className="text-sm text-base-content/70">
                        {description}
                    </p>
                </div>

                <div className="flex h-24 items-center justify-center rounded-box border border-dashed border-base-300 bg-base-200/50 p-3">
                    {preview}
                </div>

                {canUpdate && (
                    <Form
                        {...BrandingSettingController.update.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="grid gap-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="file"
                                    name={image}
                                    accept={accept}
                                    required
                                    aria-invalid={!!errors[image]}
                                    className="file-input w-full file-input-sm"
                                    data-test={`branding-${image}-file`}
                                />
                                <p className="text-xs text-base-content/60">
                                    {hint}
                                </p>
                                <InputError message={errors[image]} />

                                <Button
                                    type="submit"
                                    size="sm"
                                    className="w-fit"
                                    disabled={processing}
                                    data-test={`branding-${image}-save`}
                                >
                                    {processing
                                        ? 'Uploading…'
                                        : url === null
                                          ? 'Upload'
                                          : 'Replace'}
                                </Button>
                            </>
                        )}
                    </Form>
                )}

                {/* Outside the upload form: the dialog renders a form of its own. */}
                {canUpdate && url !== null && (
                    <ConfirmActionDialog
                        submit={BrandingSettingController.destroy.form(image)}
                        title={`Remove the ${title.toLowerCase()}?`}
                        description={
                            image === 'logo'
                                ? 'The sidebar and sign-in screens go back to the default mark. The image file is deleted.'
                                : 'The browser tab goes back to the logo, or the default icon. The image file is deleted.'
                        }
                        confirmLabel="Remove"
                        testId={`branding-${image}-remove`}
                    >
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            className="w-fit"
                        >
                            Remove {title.toLowerCase()}
                        </Button>
                    </ConfirmActionDialog>
                )}
            </div>
        </div>
    );
}

/**
 * What shows while nothing is uploaded for this image — `AppLogoMark` draws the
 * placeholder mark, or the logo when the favicon is falling back to it.
 */
function DefaultPreview({ label }: { label: string }) {
    return (
        <div className="flex flex-col items-center gap-1 text-xs text-base-content/60">
            <AppLogoMark className="h-8 w-auto" />
            {label}
        </div>
    );
}
