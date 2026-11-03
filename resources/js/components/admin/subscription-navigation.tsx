import { Link } from '@inertiajs/react';
import { index as plansIndex } from '@/routes/admin/plans';
import { index as methodsIndex } from '@/routes/admin/subscription-payment-methods';
import { index as tenantsIndex } from '@/routes/admin/tenants';

export default function SubscriptionNavigation() {
    return (
        <nav className="flex flex-wrap gap-3 border-b pb-4 text-sm">
            <Link
                href={tenantsIndex.url()}
                className="text-primary hover:underline"
            >
                Tenants
            </Link>
            <Link
                href={plansIndex.url()}
                className="text-primary hover:underline"
            >
                Subscription plans
            </Link>
            <Link
                href={methodsIndex.url()}
                className="text-primary hover:underline"
            >
                Payment collection
            </Link>
        </nav>
    );
}
