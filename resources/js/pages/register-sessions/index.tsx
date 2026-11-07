import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

interface Session {
    id: string;
    register_name: string | null;
    branch_name: string | null;
    status: string;
    opening_balance: number;
    closing_balance: number | null;
    expected_balance: number | null;
    actual_balance: number | null;
    variance: number | null;
    opened_by_name: string | null;
    opened_at: string;
    closed_at: string | null;
}

interface Props {
    sessions: {
        data: Session[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function RegisterSessionIndexPage({ sessions }: Props) {
    return (
        <>
            <Head title="Register sessions" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Register sessions"
                    description="Track open and closed register shifts."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="mt-4 divide-y">
                        {sessions.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No register sessions found.
                            </div>
                        ) : (
                            sessions.data.map((session) => (
                                <div
                                    key={session.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {session.register_name ??
                                                'Register'}{' '}
                                            â€” {session.branch_name ?? ''}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Opened by{' '}
                                            {session.opened_by_name ?? 'â€”'} at{' '}
                                            {new Date(
                                                session.opened_at,
                                            ).toLocaleString()}
                                        </p>
                                        {session.closed_at && (
                                            <p className="text-sm text-muted-foreground">
                                                Closed{' '}
                                                {new Date(
                                                    session.closed_at,
                                                ).toLocaleString()}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                session.status === 'open'
                                                    ? 'bg-success/10 text-forest dark:bg-success/15 dark:text-success'
                                                    : 'bg-secondary text-secondary-foreground'
                                            }`}
                                        >
                                            {session.status}
                                        </span>
                                        {session.variance !== null && (
                                            <span
                                                className={`text-sm ${
                                                    Number(session.variance) ===
                                                    0
                                                        ? 'text-success'
                                                        : 'text-destructive'
                                                }`}
                                            >
                                                Variance:{' '}
                                                {Number(
                                                    session.variance,
                                                ).toFixed(2)}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

RegisterSessionIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Register sessions',
            href: '/register-sessions',
        },
    ],
};
