import { Link, usePage } from '@inertiajs/react';
import { Menu, Moon, Sun, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import Brand from '@/components/landing/brand';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { dashboard, login, register } from '@/routes';
import { marketplace } from '@/routes/storefront';

const navLinks = [
    { label: 'Features', href: '#features' },
    { label: 'How it works', href: '#how-it-works' },
    { label: 'Pricing', href: '#pricing' },
    { label: 'FAQ', href: '#faq' },
];

/**
 * Public navigation for the landing page.
 */
export default function LandingHeader() {
    const { auth } = usePage().props;
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [isScrolled, setIsScrolled] = useState(false);
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setIsScrolled(window.scrollY > 8);

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <header
            className={cn(
                'fixed inset-x-0 top-0 z-50 transition-[background-color,box-shadow,border-color] duration-300',
                isScrolled || isMenuOpen
                    ? 'border-b bg-background/85 shadow-xs backdrop-blur-md'
                    : 'border-b border-transparent',
            )}
        >
            <div className="mx-auto flex h-16 w-full max-w-6xl items-center gap-4 px-5 sm:px-8">
                <a href="#top" className="shrink-0">
                    <Brand />
                </a>

                <nav className="ml-6 hidden items-center gap-1 lg:flex">
                    {navLinks.map((link) => (
                        <a
                            key={link.href}
                            href={link.href}
                            className="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                        >
                            {link.label}
                        </a>
                    ))}
                    <Link
                        href={marketplace.url()}
                        className="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                    >
                        Marketplace
                    </Link>
                </nav>

                <div className="ml-auto flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={
                            resolvedAppearance === 'dark'
                                ? 'Switch to light mode'
                                : 'Switch to dark mode'
                        }
                        onClick={() =>
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        }
                    >
                        {resolvedAppearance === 'dark' ? (
                            <Sun className="size-5" />
                        ) : (
                            <Moon className="size-5" />
                        )}
                    </Button>

                    {auth.user ? (
                        <Button asChild className="hidden sm:inline-flex">
                            <Link href={dashboard()} prefetch>
                                Open dashboard
                            </Link>
                        </Button>
                    ) : (
                        <>
                            <Button
                                asChild
                                variant="ghost"
                                className="hidden sm:inline-flex"
                            >
                                <Link href={login()} prefetch>
                                    Log in
                                </Link>
                            </Button>
                            <Button asChild className="hidden sm:inline-flex">
                                <Link href={register()} prefetch>
                                    Start free trial
                                </Link>
                            </Button>
                        </>
                    )}

                    <Button
                        variant="ghost"
                        size="icon"
                        className="lg:hidden"
                        aria-label={isMenuOpen ? 'Close menu' : 'Open menu'}
                        aria-expanded={isMenuOpen}
                        onClick={() => setIsMenuOpen((open) => !open)}
                    >
                        {isMenuOpen ? (
                            <X className="size-5" />
                        ) : (
                            <Menu className="size-5" />
                        )}
                    </Button>
                </div>
            </div>

            {isMenuOpen && (
                <div className="border-t bg-background/95 backdrop-blur-md lg:hidden">
                    <nav className="mx-auto flex w-full max-w-6xl flex-col gap-1 px-5 py-4 sm:px-8">
                        {navLinks.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                onClick={() => setIsMenuOpen(false)}
                                className="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                            >
                                {link.label}
                            </a>
                        ))}
                        <Link
                            href={marketplace.url()}
                            onClick={() => setIsMenuOpen(false)}
                            className="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                        >
                            Marketplace
                        </Link>
                        <div className="mt-2 flex flex-col gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>
                                        Open dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="outline">
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>
                                            Start free trial
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </nav>
                </div>
            )}
        </header>
    );
}
