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
                    <div className="relative overflow-hidden rounded-3xl bg-forest px-6 py-14 text-center text-sidebar-foreground sm:px-12">
                        <div
                            aria-hidden="true"
                            className="absolute -top-24 left-1/2 size-72 -translate-x-1/2 rounded-full bg-lime/20 blur-3xl motion-safe:animate-drift"
                        />
                        <div
                            aria-hidden="true"
                            className="absolute right-10 -bottom-32 size-64 rounded-full bg-commerce/25 blur-3xl motion-safe:animate-float"
                        />

                        <div className="relative mx-auto max-w-2xl">
                            <h2 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                Ready to run your shop on Sokora?
                            </h2>
                            <p className="mt-4 text-lg text-pretty text-sidebar-foreground/75">
                                Create your workspace, load your catalogue and
                                start selling. No card required.
                            </p>

                            <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                {auth.user ? (
                                    <Button
                                        asChild
                                        variant="accent"
                                        size="lg"
                                        className="w-full sm:w-auto"
                                    >
                                        <Link href={dashboard()}>
                                            Open dashboard
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        asChild
                                        variant="accent"
                                        size="lg"
                                        className="w-full sm:w-auto"
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
                                    className="w-full border-white/25 bg-transparent text-sidebar-foreground hover:bg-white/10 hover:text-sidebar-foreground sm:w-auto"
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
