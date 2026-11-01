import { ClipboardList, LayoutDashboard, Store } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import Reveal from '@/components/landing/reveal';

type Step = {
    icon: LucideIcon;
    title: string;
    description: string;
};

const steps: Step[] = [
    {
        icon: ClipboardList,
        title: 'Set up your catalogue',
        description:
            'Add your products, prices, tax rates and discounts, then record the opening stock for each branch.',
    },
    {
        icon: Store,
        title: 'Open a register and sell',
        description:
            'Start a shift, ring up sales with any payment method, print receipts and handle returns on the spot.',
    },
    {
        icon: LayoutDashboard,
        title: 'Act on the numbers',
        description:
            'Sales, stock and expenses update the dashboard and reports as you trade, so restocking never comes as a surprise.',
    },
];

/**
 * Three-step walkthrough of the daily workflow.
 */
export default function LandingWorkflow() {
    return (
        <section
            id="how-it-works"
            className="scroll-mt-24 border-y border-border bg-muted/30 py-20 lg:py-28"
        >
            <div className="mx-auto w-full max-w-6xl px-5 sm:px-8">
                <Reveal className="mx-auto max-w-2xl text-center">
                    <p className="text-sm font-semibold tracking-wider text-emerald-600 uppercase dark:text-emerald-400">
                        How it works
                    </p>
                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        From opening stock to closing the till
                    </h2>
                    <p className="mt-4 text-lg text-pretty text-muted-foreground">
                        A simple daily rhythm that keeps the counter moving and
                        the books honest.
                    </p>
                </Reveal>

                <div className="relative mt-14">
                    <div
                        aria-hidden="true"
                        className="absolute inset-x-12 top-9 hidden h-px bg-gradient-to-r from-transparent via-border to-transparent lg:block"
                    />

                    <div className="grid gap-8 lg:grid-cols-3 lg:gap-6">
                        {steps.map((step, index) => (
                            <Reveal key={step.title} delay={index * 90}>
                                <div className="relative flex h-full flex-col items-center text-center lg:px-4">
                                    <span className="relative z-10 flex size-18 items-center justify-center rounded-2xl border border-border bg-card shadow-sm">
                                        <step.icon className="size-7 text-emerald-600 dark:text-emerald-400" />
                                    </span>
                                    <span className="mt-5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                        Step {index + 1}
                                    </span>
                                    <h3 className="mt-2 text-lg font-semibold">
                                        {step.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        {step.description}
                                    </p>
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
