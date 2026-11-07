const businessTypes = [
    'Retail stores',
    'CafÃ©s & restaurants',
    'Pharmacies',
    'Electronics',
    'Fashion & apparel',
    'Hardware',
    'Groceries',
    'Wholesale & distribution',
];

/**
 * Scrolling strip of the kinds of businesses TickSales is built for.
 */
export default function LandingProof() {
    return (
        <section
            aria-label="Businesses built for Sokora"
            className="border-y border-border bg-muted/30 py-6"
        >
            <div className="mx-auto flex w-full max-w-6xl flex-col items-center gap-4 px-5 sm:px-8 md:flex-row md:gap-8">
                <p className="shrink-0 text-sm font-medium text-muted-foreground">
                    Built for teams in
                </p>
                <div className="relative w-full overflow-hidden [mask-image:linear-gradient(to_right,transparent,#000_12%,#000_88%,transparent)]">
                    <div className="flex w-max hover:[animation-play-state:paused] motion-safe:animate-ticker">
                        {[...businessTypes, ...businessTypes].map(
                            (business, index) => (
                                <span
                                    key={`${business}-${index}`}
                                    aria-hidden={
                                        index >= businessTypes.length ||
                                        undefined
                                    }
                                    className="px-8 text-sm font-medium whitespace-nowrap text-muted-foreground/80"
                                >
                                    {business}
                                </span>
                            ),
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
