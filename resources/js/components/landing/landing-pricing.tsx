import { Link } from '@inertiajs/react';
import { Check, Sparkles } from 'lucide-react';
import { useState } from 'react';

import Reveal from '@/components/landing/reveal';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { register } from '@/routes';
import type { LandingPlan } from '@/types';

type BillingCycle = 'monthly' | 'yearly';

const ANNUAL_DISCOUNT = 0.2;

const featureLabels: Record<string, string> = {
    pos: 'Point of sale, registers and receipts',
    products: 'Product catalogue, barcodes and variants',
    customers: 'Customers, groups and supplier records',
    inventory_basic: 'Stock levels and low-stock alerts',
    inventory_advanced: 'Stock transfers, adjustments and ledger',
    reports: 'Sales, inventory and expense reports',
    multi_branch: 'Multi-branch operations',
    custom_branding: 'Custom branding and theme presets',
    priority_support: 'Priority support',
};

const formatPrice = (value: number): string =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(value);

const branchLabel = (maxBranches: number): string => {
    if (maxBranches >= 9999) {
        return 'Unlimited branches';
    }

    return `${maxBranches} branch${maxBranches === 1 ? '' : 'es'}`;
};

/**
 * Subscription plans section, driven by the plans configured in the database.
 */
export default function LandingPricing({ plans }: { plans: LandingPlan[] }) {
    const [billingCycle, setBillingCycle] = useState<BillingCycle>('monthly');
    const isYearly = billingCycle === 'yearly';

    return (
        <section id="pricing" className="scroll-mt-24 py-20 lg:py-28">
            <div className="mx-auto w-full max-w-6xl px-5 sm:px-8">
                <Reveal className="mx-auto max-w-2xl text-center">
                    <p className="text-sm font-semibold tracking-wider text-emerald-600 uppercase dark:text-emerald-400">
                        Plans
                    </p>
                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        Simple pricing that grows with you
                    </h2>
                    <p className="mt-4 text-lg text-pretty text-muted-foreground">
                        Start with a single counter, add branches as you expand.
                        Every plan includes the full point of sale.
                    </p>

                    <div className="mt-8 inline-flex items-center gap-1 rounded-full border border-border bg-muted/50 p-1">
                        {(
                            [
                                { value: 'monthly', label: 'Monthly' },
                                { value: 'yearly', label: 'Yearly' },
                            ] as const
                        ).map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => setBillingCycle(option.value)}
                                className={cn(
                                    'rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
                                    billingCycle === option.value
                                        ? 'bg-background text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {option.label}
                                {option.value === 'yearly' && (
                                    <span className="ml-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        -20%
                                    </span>
                                )}
                            </button>
                        ))}
                    </div>
                </Reveal>

                <div className="mt-14 grid items-start gap-6 lg:grid-cols-3">
                    {plans.map((plan, index) => {
                        const isPopular = plan.name === 'pro';
                        const price = isYearly
                            ? Number(
                                  (
                                      plan.price_monthly *
                                      (1 - ANNUAL_DISCOUNT)
                                  ).toFixed(2),
                              )
                            : plan.price_monthly;

                        return (
                            <Reveal key={plan.name} delay={index * 80}>
                                <article
                                    className={cn(
                                        'relative flex h-full flex-col rounded-2xl border p-6 shadow-sm transition-[transform,box-shadow] duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-black/5 dark:hover:shadow-black/20',
                                        isPopular
                                            ? 'border-emerald-500/60 bg-card ring-1 ring-emerald-500/30 lg:scale-[1.03]'
                                            : 'border-border bg-card',
                                    )}
                                >
                                    {isPopular && (
                                        <span className="absolute -top-3 left-6 inline-flex items-center gap-1 rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white shadow-sm">
                                            <Sparkles className="size-3.5" />
                                            Most popular
                                        </span>
                                    )}

                                    <h3 className="text-lg font-semibold">
                                        {plan.display_name}
                                    </h3>
                                    <p className="mt-1.5 min-h-10 text-sm text-muted-foreground">
                                        {plan.description}
                                    </p>

                                    <div className="mt-5 flex items-baseline gap-1.5">
                                        <span className="text-4xl font-semibold tracking-tight">
                                            {formatPrice(price)}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            /month
                                        </span>
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {isYearly
                                            ? 'Billed yearly · cancel anytime'
                                            : 'Billed monthly · cancel anytime'}
                                    </p>

                                    <ul className="mt-6 flex-1 space-y-3">
                                        <li className="flex items-start gap-2.5 text-sm">
                                            <Check className="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                            <span>
                                                {branchLabel(plan.max_branches)}
                                            </span>
                                        </li>
                                        {plan.features.map((feature) => (
                                            <li
                                                key={feature}
                                                className="flex items-start gap-2.5 text-sm"
                                            >
                                                <Check className="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                                <span>
                                                    {featureLabels[feature] ??
                                                        feature}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>

                                    <Button
                                        asChild
                                        size="lg"
                                        variant={
                                            isPopular ? 'default' : 'outline'
                                        }
                                        className="mt-7 w-full"
                                    >
                                        <Link href={register()}>
                                            Start free trial
                                        </Link>
                                    </Button>
                                </article>
                            </Reveal>
                        );
                    })}
                </div>

                <Reveal className="mt-10 text-center">
                    <p className="text-sm text-muted-foreground">
                        Every plan includes the full point of sale, product
                        catalogue and customer records. No setup fees, cancel
                        whenever you like.
                    </p>
                </Reveal>
            </div>
        </section>
    );
}
