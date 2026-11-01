import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

import Reveal from '@/components/landing/reveal';
import { Button } from '@/components/ui/button';
import { dashboard, register } from '@/routes';

/**
 * Closing call to action.
 */
export default function LandingCta() {
    const { auth } = usePage().props;

    return (
        <section className="pb-20 lg:pb-28">
            <div className="mx-auto w-full max-w-6xl px-5 sm:px-8">
                <Reveal>
                    <div className="relative overflow-hidden rounded-3xl bg-neutral-950 px-6 py-14 text-center text-neutral-50 sm:px-12 dark:bg-neutral-900/70 dark:ring-1 dark:ring-border">
                        <div
                            aria-hidden="true"
                            className="absolute -top-24 left-1/2 size-72 -translate-x-1/2 rounded-full bg-emerald-500/25 blur-3xl motion-safe:animate-drift"
                        />
                        <div
                            aria-hidden="true"
                            className="absolute right-10 -bottom-32 size-64 rounded-full bg-sky-500/20 blur-3xl motion-safe:animate-float"
                        />

                        <div className="relative mx-auto max-w-2xl">
                            <h2 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                Ready to run your shop on TickSales?
                            </h2>
                            <p className="mt-4 text-lg text-pretty text-neutral-300">
                                Create your workspace, load your catalogue and
                                start selling. No card required.
                            </p>

                            <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                {auth.user ? (
                                    <Button
                                        asChild
                                        size="lg"
                                        className="w-full bg-white text-neutral-950 hover:bg-neutral-200 sm:w-auto dark:bg-neutral-50 dark:text-neutral-900"
                                    >
                                        <Link href={dashboard()}>
                                            Open dashboard
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        asChild
                                        size="lg"
                                        className="w-full bg-white text-neutral-950 hover:bg-neutral-200 sm:w-auto dark:bg-neutral-50 dark:text-neutral-900"
                                    >
                                        <Link href={register()}>
                                            Start free trial
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                )}

                                <Button
                                    asChild
                                    size="lg"
                                    variant="outline"
                                    className="w-full border-neutral-700 bg-transparent text-neutral-100 hover:bg-neutral-800 hover:text-white sm:w-auto"
                                >
                                    <a href="#pricing">Compare plans</a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
