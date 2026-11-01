import { Head, usePage } from '@inertiajs/react';

import LandingCta from '@/components/landing/landing-cta';
import LandingFaq from '@/components/landing/landing-faq';
import LandingFeatures from '@/components/landing/landing-features';
import LandingFooter from '@/components/landing/landing-footer';
import LandingHeader from '@/components/landing/landing-header';
import LandingHero from '@/components/landing/landing-hero';
import LandingPricing from '@/components/landing/landing-pricing';
import LandingProof from '@/components/landing/landing-proof';
import LandingWorkflow from '@/components/landing/landing-workflow';
import type { LandingPlan } from '@/types';

export default function Welcome() {
    const { plans } = usePage<{ plans: LandingPlan[] }>().props;

    return (
        <>
            <Head title="Point of sale and inventory for growing businesses" />

            <div className="landing-page min-h-screen scroll-smooth bg-background text-foreground">
                <LandingHeader />

                <main>
                    <LandingHero />
                    <LandingProof />
                    <LandingFeatures />
                    <LandingWorkflow />
                    <LandingPricing plans={plans} />
                    <LandingFaq />
                    <LandingCta />
                </main>

                <LandingFooter />
            </div>
        </>
    );
}
