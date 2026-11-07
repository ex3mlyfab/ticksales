import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    CloudOff,
    CreditCard,
    PackageCheck,
    Sparkles,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { dashboard, register } from '@/routes';
import { marketplace } from '@/routes/storefront';

const catalogueTiles = [
    { name: 'Arabica Coffee', price: '4.50' },
    { name: 'Croissant', price: '3.20' },
    { name: 'Sparkling Water', price: '2.10' },
    { name: 'House Blend 250g', price: '12.00' },
];

const cartLines = [
    { name: 'Arabica Coffee', quantity: '2', total: '9.00' },
    { name: 'Croissant', quantity: '1', total: '3.20' },
    { name: 'Sparkling Water', quantity: '1', total: '2.10' },
];

/**
 * Hero section: the headline, calls to action and a lightly animated
 * preview of the point-of-sale screen.
 */
export default function LandingHero() {
    const { auth } = usePage().props;

    return (
        <section
            id="top"
            className="relative overflow-hidden pt-28 pb-14 sm:pt-32 lg:pt-40 lg:pb-24"
        >
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 -z-10"
            >
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,var(--border)_1px,transparent_0)] [mask-image:radial-gradient(ellipse_70%_60%_at_50%_0%,#000_10%,transparent_75%)] [background-size:28px_28px] opacity-50 dark:opacity-25" />
                <div className="absolute -top-40 left-1/2 size-[34rem] -translate-x-1/2 rounded-full bg-emerald-400/20 blur-3xl motion-safe:animate-drift dark:bg-emerald-500/10" />
                <div className="absolute top-10 right-[-6rem] size-72 rounded-full bg-sky-400/20 blur-3xl motion-safe:animate-float dark:bg-sky-500/10" />
            </div>

            <div className="mx-auto grid w-full max-w-6xl items-center gap-12 px-5 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:gap-16">
                <div className="text-center lg:text-left">
                    <span className="inline-flex items-center gap-2 rounded-full border border-border bg-background/80 px-3 py-1 text-xs font-medium text-muted-foreground shadow-xs backdrop-blur motion-safe:animate-rise">
                        <Sparkles className="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        Multi-tenant POS for retail, food and distribution
                    </span>

                    <h1
                        className="mt-6 text-4xl font-semibold tracking-tight text-balance motion-safe:animate-rise sm:text-5xl lg:text-6xl"
                        style={{ animationDelay: '80ms' }}
                    >
                        One platform to run every till, stockroom and branch.
                    </h1>

                    <p
                        className="mx-auto mt-6 max-w-xl text-lg text-pretty text-muted-foreground motion-safe:animate-rise lg:mx-0"
                        style={{ animationDelay: '160ms' }}
                    >
                        TickSales brings point of sale, inventory, purchasing,
                        customers and reporting together, so your team can serve
                        the counter faster and know exactly what is selling.
                        Your public marketplace also keeps products available
                        to browse and buy online.
                    </p>

                    <div
                        className="mt-8 flex flex-col items-center gap-3 motion-safe:animate-rise sm:flex-row sm:justify-center lg:justify-start"
                        style={{ animationDelay: '240ms' }}
                    >
                        {auth.user ? (
                            <Button
                                asChild
                                size="lg"
                                className="w-full sm:w-auto"
                            >
                                <Link href={dashboard()} prefetch>
                                    Open dashboard
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        ) : (
                            <Button
                                asChild
                                size="lg"
                                className="w-full sm:w-auto"
                            >
                                <Link href={register()} prefetch>
                                    Start free trial
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        )}

                        <Button
                            asChild
                            size="lg"
                            variant="outline"
                            className="w-full sm:w-auto"
                        >
                            <Link href={marketplace.url()}>
                                Browse marketplace
                            </Link>
                        </Button>
                    </div>

                    <ul
                        className="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-muted-foreground motion-safe:animate-rise lg:justify-start"
                        style={{ animationDelay: '320ms' }}
                    >
                        <li className="flex items-center gap-1.5">
                            <CheckCircle2 className="size-4 text-emerald-600 dark:text-emerald-400" />
                            No card required
                        </li>
                        <li className="flex items-center gap-1.5">
                            <CheckCircle2 className="size-4 text-emerald-600 dark:text-emerald-400" />
                            Works offline
                        </li>
                        <li className="flex items-center gap-1.5">
                            <CheckCircle2 className="size-4 text-emerald-600 dark:text-emerald-400" />
                            Cancel anytime
                        </li>
                        <li className="flex items-center gap-1.5">
                            <CheckCircle2 className="size-4 text-emerald-600 dark:text-emerald-400" />
                            Public marketplace included
                        </li>
                    </ul>
                </div>

                <div className="relative mx-auto w-full max-w-lg lg:max-w-none">
                    <div
                        aria-hidden="true"
                        className="absolute -inset-8 rounded-[2.5rem] bg-gradient-to-br from-emerald-400/25 via-sky-400/15 to-transparent blur-2xl motion-safe:animate-pulse"
                    />

                    <div className="relative overflow-hidden rounded-2xl border border-border bg-card shadow-xl shadow-black/5 motion-safe:animate-float dark:shadow-black/30">
                        <div className="flex items-center justify-between border-b border-border bg-muted/40 px-4 py-3">
                            <div className="flex items-center gap-2">
                                <span className="flex size-2.5 rounded-full bg-emerald-500 motion-safe:animate-pulse" />
                                <span className="text-sm font-medium">
                                    Register 1 · Front counter
                                </span>
                            </div>
                            <span className="text-xs text-muted-foreground">
                                Shift 09:41
                            </span>
                        </div>

                        <div className="grid grid-cols-5 gap-3 p-4">
                            <div className="col-span-3 space-y-2">
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Catalogue
                                </p>
                                <div className="grid grid-cols-2 gap-2">
                                    {catalogueTiles.map((tile) => (
                                        <div
                                            key={tile.name}
                                            className="rounded-lg border border-border bg-background p-2.5 transition-transform duration-200 hover:-translate-y-0.5"
                                        >
                                            <div className="mb-2 h-8 rounded-md bg-gradient-to-br from-muted to-muted/40" />
                                            <p className="truncate text-xs font-medium">
                                                {tile.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                ${tile.price}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="col-span-2 flex flex-col rounded-lg border border-border bg-background p-3">
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Current sale
                                </p>
                                <ul className="mt-2 flex-1 space-y-1.5">
                                    {cartLines.map((line) => (
                                        <li
                                            key={line.name}
                                            className="flex items-center justify-between gap-2 text-xs"
                                        >
                                            <span className="truncate text-muted-foreground">
                                                {line.quantity} × {line.name}
                                            </span>
                                            <span className="font-medium">
                                                ${line.total}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                                <div className="mt-3 border-t border-border pt-2">
                                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                                        <span>Tax</span>
                                        <span>$1.14</span>
                                    </div>
                                    <div className="mt-1 flex items-center justify-between text-sm font-semibold">
                                        <span>Total</span>
                                        <span>$15.44</span>
                                    </div>
                                    <div className="mt-3 flex items-center justify-center gap-1.5 rounded-md bg-primary py-2 text-xs font-medium text-primary-foreground">
                                        <CreditCard className="size-3.5" />
                                        Charge $15.44
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        className="absolute -top-6 -right-2 hidden items-center gap-2 rounded-xl border border-border bg-background/95 px-3 py-2 shadow-lg backdrop-blur motion-safe:animate-float sm:flex"
                        style={{ animationDelay: '600ms' }}
                    >
                        <CheckCircle2 className="size-4 text-emerald-600 dark:text-emerald-400" />
                        <span className="text-xs font-medium">
                            Sale completed · $15.44
                        </span>
                    </div>

                    <div
                        className="absolute -bottom-6 -left-3 hidden items-center gap-2 rounded-xl border border-border bg-background/95 px-3 py-2 shadow-lg backdrop-blur motion-safe:animate-float sm:flex"
                        style={{ animationDelay: '1.2s' }}
                    >
                        <PackageCheck className="size-4 text-sky-600 dark:text-sky-400" />
                        <span className="text-xs font-medium">
                            Stock updated · 12 units
                        </span>
                    </div>

                    <div
                        className="absolute -right-4 bottom-16 hidden items-center gap-2 rounded-xl border border-border bg-background/95 px-3 py-2 shadow-lg backdrop-blur motion-safe:animate-float lg:flex"
                        style={{ animationDelay: '1.8s' }}
                    >
                        <CloudOff className="size-4 text-amber-600 dark:text-amber-400" />
                        <span className="text-xs font-medium">
                            Offline sales sync later
                        </span>
                    </div>
                </div>
            </div>
        </section>
    );
}
