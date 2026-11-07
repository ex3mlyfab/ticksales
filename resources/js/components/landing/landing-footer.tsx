import { Link, usePage } from '@inertiajs/react';

import Brand from '@/components/landing/brand';
import { dashboard, login, register } from '@/routes';
import { marketplace } from '@/routes/storefront';

const productLinks = [
    { label: 'Features', href: '#features' },
    { label: 'How it works', href: '#how-it-works' },
    { label: 'Pricing', href: '#pricing' },
    { label: 'FAQ', href: '#faq' },
];

/**
 * Landing page footer.
 */
export default function LandingFooter() {
    const { auth, name } = usePage().props;

    return (
        <footer className="border-t border-border bg-muted/30">
            <div className="mx-auto w-full max-w-6xl px-5 py-12 sm:px-8">
                <div className="flex flex-col gap-10 md:flex-row md:justify-between">
                    <div className="max-w-sm">
                        <Brand />
                        <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                            {name} is a multi-tenant point of sale and
                            inventory platform for retail, food and distribution
                            businesses.
                        </p>
                    </div>

                    <div className="grid grid-cols-2 gap-8 sm:grid-cols-3">
                        <div>
                            <h3 className="text-sm font-semibold">Product</h3>
                            <ul className="mt-4 space-y-3">
                                {productLinks.map((link) => (
                                    <li key={link.href}>
                                        <a
                                            href={link.href}
                                            className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                        >
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                                <li>
                                    <Link
                                        href={marketplace.url()}
                                        className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                    >
                                        Marketplace
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 className="text-sm font-semibold">Account</h3>
                            <ul className="mt-4 space-y-3">
                                {auth.user ? (
                                    <li>
                                        <Link
                                            href={dashboard()}
                                            className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                        >
                                            Dashboard
                                        </Link>
                                    </li>
                                ) : (
                                    <>
                                        <li>
                                            <Link
                                                href={login()}
                                                className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                            >
                                                Log in
                                            </Link>
                                        </li>
                                        <li>
                                            <Link
                                                href={register()}
                                                className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                            >
                                                Create a workspace
                                            </Link>
                                        </li>
                                    </>
                                )}
                            </ul>
                        </div>

                        <div className="col-span-2 sm:col-span-1">
                            <h3 className="text-sm font-semibold">
                                Get started
                            </h3>
                            <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                                Load your catalogue, open a register and see
                                your first sale on the dashboard today.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="mt-12 flex flex-col items-center justify-between gap-4 border-t border-border pt-6 sm:flex-row">
                    <p className="text-xs text-muted-foreground">
                        Â© {new Date().getFullYear()} {name}. All rights
                        reserved.
                    </p>
                    <p className="text-xs text-muted-foreground">
                        Built for retailers who like their numbers tidy.
                    </p>
                </div>
            </div>
        </footer>
    );
}
