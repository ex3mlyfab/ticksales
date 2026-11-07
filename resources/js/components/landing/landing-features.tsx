import {
    BarChart3,
    Boxes,
    Building2,
    CloudOff,
    Package,
    ShoppingBag,
    Plug,
    ShieldCheck,
    ShoppingCart,
    Truck,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import Reveal from '@/components/landing/reveal';

type Offering = {
    icon: LucideIcon;
    title: string;
    description: string;
    points: string[];
};

const offerings: Offering[] = [
    {
        icon: ShoppingCart,
        title: 'Point of sale',
        description:
            'A touch-friendly checkout screen built for busy counters, with register sessions, cash movements and printable receipts.',
        points: [
            'Register sessions & shifts',
            'Multiple payment methods',
            'Receipts and sale returns',
        ],
    },
    {
        icon: Boxes,
        title: 'Inventory & stock',
        description:
            'Know what is on hand in every branch. Stock only moves through adjustments, transfers and receipts, so the ledger always adds up.',
        points: [
            'Live stock levels',
            'Adjustments & transfers',
            'Low-stock alerts',
        ],
    },
    {
        icon: Package,
        title: 'Product catalogue',
        description:
            'Model your range properly with categories, brands, units, variants, barcodes, tax rates and discounts.',
        points: [
            'Units & variants',
            'Barcodes & scanning',
            'Tax rates & discounts',
        ],
    },
    {
        icon: Truck,
        title: 'Purchasing',
        description:
            'Plan replenishment with purchase orders, record what actually arrived, and handle purchase returns without spreadsheets.',
        points: ['Purchase orders', 'Receive deliveries', 'Purchase returns'],
    },
    {
        icon: Users,
        title: 'Customers & suppliers',
        description:
            'Keep every relationship in one place, with customer groups for pricing and supplier records for purchasing.',
        points: [
            'Customer accounts & groups',
            'Supplier records',
            'Linked sale history',
        ],
    },
    {
        icon: BarChart3,
        title: 'Reports & analytics',
        description:
            'A dashboard that answers the daily questions: what sold, what is running out, and where the money went.',
        points: [
            'Sales & inventory reports',
            'Expense tracking',
            'Exportable data',
        ],
    },
];

const platformCapabilities = [
    {
        icon: ShoppingBag,
        title: 'Public marketplace',
        description:
            'Make stores and products available to shoppers through the online marketplace.',
    },
    {
        icon: ShieldCheck,
        title: 'Roles & permissions',
        description:
            'Give cashiers, managers and accountants exactly the access they need.',
    },
    {
        icon: Building2,
        title: 'Multi-branch',
        description:
            'Run several locations from one account with per-branch stock and registers.',
    },
    {
        icon: CloudOff,
        title: 'Offline ready',
        description:
            'Keep selling when the connection drops. Sales sync back when you are online.',
    },
    {
        icon: Plug,
        title: 'API & integrations',
        description:
            'A versioned REST API and webhooks for custom tools and mobile clients.',
    },
];

/**
 * Cards summarising what the application offers.
 */
export default function LandingFeatures() {
    return (
        <section id="features" className="scroll-mt-24 py-20 lg:py-28">
            <div className="mx-auto w-full max-w-6xl px-5 sm:px-8">
                <Reveal className="mx-auto max-w-2xl text-center">
                    <p className="text-sm font-semibold tracking-wider text-commerce uppercase dark:text-commerce">
                        What you get
                    </p>
                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        Everything a growing retailer needs, in one place
                    </h2>
                    <p className="mt-4 text-lg text-pretty text-muted-foreground">
                        Replace the patchwork of tills, spreadsheets and
                        notebooks with one system your whole team can trust.
                    </p>
                </Reveal>

                <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {offerings.map((offering, index) => (
                        <Reveal key={offering.title} delay={index * 60}>
                            <article className="group h-full rounded-2xl border border-border bg-card p-6 shadow-sm transition-[transform,box-shadow] duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/5 dark:hover:shadow-black/20">
                                <span className="flex size-11 items-center justify-center rounded-xl bg-commerce/10 text-commerce transition-colors group-hover:bg-commerce/15 dark:text-commerce">
                                    <offering.icon className="size-5" />
                                </span>
                                <h3 className="mt-4 text-lg font-semibold">
                                    {offering.title}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                    {offering.description}
                                </p>
                                <ul className="mt-4 space-y-2">
                                    {offering.points.map((point) => (
                                        <li
                                            key={point}
                                            className="flex items-center gap-2 text-sm text-muted-foreground"
                                        >
                                            <span className="size-1.5 rounded-full bg-commerce" />
                                            {point}
                                        </li>
                                    ))}
                                </ul>
                            </article>
                        </Reveal>
                    ))}
                </div>

                <Reveal className="mt-6">
                    <div className="grid gap-4 rounded-2xl border border-border bg-muted/40 p-6 sm:grid-cols-2 lg:grid-cols-4">
                        {platformCapabilities.map((capability) => (
                            <div key={capability.title} className="flex gap-3">
                                <capability.icon className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                <div>
                                    <h3 className="text-sm font-semibold">
                                        {capability.title}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {capability.description}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
