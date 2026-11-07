import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import TenantBrandingSettingsController from '@/actions/App/Http/Controllers/Settings/TenantBrandingSettingsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

type PermittedAccent = {
    key: string;
    name: string;
    hex: string;
};

/*
 | Curated accents a tenant may choose from (App\Enums\TenantAccent).
 | Sokora's master brand — Deep Forest, Electric Lime, Midnight — is
 | platform-owned and never selectable here.
 */
const permittedAccents: PermittedAccent[] = [
    { key: 'default', name: 'Sokora default', hex: '' },
    { key: 'evergreen', name: 'Evergreen', hex: '#279C78' },
    { key: 'harbor', name: 'Harbor', hex: '#4389C7' },
    { key: 'harvest', name: 'Harvest', hex: '#E8A33A' },
];

type Props = {
    branding: {
        name: string;
        logo_path: string | null;
        favicon_path: string | null;
        accent_color: string | null;
    };
    canEdit: boolean;
};

export default function TenantBrandingPage({ branding, canEdit }: Props) {
    const form = useForm({
        accent_color: permittedAccents.find(
            (accent) => accent.hex !== '' && accent.hex === branding.accent_color,
        )?.hex ?? '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.post(TenantBrandingSettingsController.update.url(), {
            preserveScroll: true,
        });
    }

    const activeAccent = form.data.accent_color;

    return (
        <>
            <Head title="Branding" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Branding"
                    description="Personalise how your business appears — while Sokora's foundation stays consistent for everyone."
                />

                <form
                    onSubmit={submit}
                    className="rounded-xl border bg-card p-6 shadow-sm"
                >
                    <div className="space-y-6">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Business identity
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Your business name and logo represent your
                                store across Sokora. Logo uploads are managed
                                with the platform team; the preview below
                                shows how your brand currently appears.
                            </p>
                        </div>

                        <div className="flex items-center gap-4 rounded-lg border bg-app-canvas p-4">
                            <AppLogoIcon className="size-10 shrink-0 text-lime" />
                            <div>
                                <p className="font-display text-sm font-semibold">
                                    {branding.name || 'Your business'}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {branding.logo_path
                                        ? 'Custom logo in use'
                                        : 'Using the Sokora default mark'}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold">Accent</h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Choose one accent from Sokora's curated palette.
                                It tints tenant-owned surfaces like your
                                storefront and receipts; platform
                                foundations — colours, sidebar, themes — are
                                managed by Sokora and stay the same for every
                                business.
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {permittedAccents.map((accent) => {
                                const selected =
                                    activeAccent === accent.hex;
                                const isDefault = accent.key === 'default';

                                return (
                                    <button
                                        type="button"
                                        key={accent.key}
                                        onClick={() => form.setData('accent_color', accent.hex)}
                                        disabled={!canEdit || form.processing}
                                        className={cn(
                                            'group flex items-center gap-3 rounded-lg border p-3 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-60',
                                            selected
                                                ? 'border-ring ring-1 ring-ring'
                                                : 'border-border hover:border-ring/50',
                                        )}
                                        aria-pressed={selected}
                                    >
                                        <span
                                            className="size-8 shrink-0 rounded-md border border-border"
                                            style={{
                                                backgroundColor:
                                                    isDefault ? 'var(--lime)' : accent.hex,
                                            }}
                                            aria-hidden="true"
                                        />
                                        <span className="text-sm font-medium">
                                            {accent.name}
                                        </span>
                                        {isDefault && (
                                            <Badge
                                                variant="secondary"
                                                className="ml-auto shrink-0"
                                            >
                                                Built-in
                                            </Badge>
                                        )}
                                    </button>
                                );
                            })}
                        </div>

                        <InputError message={form.errors.accent_color} />

                        {canEdit && (
                            <Button disabled={form.processing}>
                                {form.processing ? 'Saving…' : 'Save changes'}
                            </Button>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
}

TenantBrandingPage.layout = {
    breadcrumbs: [
        {
            title: 'Branding',
            href: '/settings/branding',
        },
    ],
};
