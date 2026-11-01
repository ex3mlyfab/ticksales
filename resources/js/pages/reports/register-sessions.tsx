import { Head, Link } from '@inertiajs/react';
import { Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Label } from '@/components/ui';
import { registerSessions } from '@/routes/reports';


interface Branch {
    id: string;
    name: string;
}

interface Session {
    id: string;
    status: string;
    opening_balance: string;
    closing_balance: string | null;
    variance: string | null;
    opened_at: string;
    closed_at: string | null;
    register?: { name: string } | null;
    branch?: { name: string } | null;
    opened_by?: { name: string } | null;
    closed_by?: { name: string } | null;
}

interface Props {
    sessions: {
        data: Session[];
        links: Array<{ url: string; label: string; active: boolean }>;
    };
    filters: {
        branch_id: string | null;
    };
    branches: Branch[];
}

export default function RegisterSessionsReportPage({
    sessions,
    filters,
    branches,
}: Props) {
    const form = useForm({
        branch_id: filters.branch_id ?? '',
    });

    return (
        <>
            <Head title="Register sessions" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Register sessions"
                    description="Review cash drawer performance per shift."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        method="get"
                        action={registerSessions.url()}
                        className="flex flex-wrap items-end gap-4"
                    >
                        {({ processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="branch_id">Branch</Label>
                                    <select
                                        id="branch_id"
                                        name="branch_id"
                                        value={form.data.branch_id}
                                        onChange={(e) =>
                                            form.setData(
                                                'branch_id',
                                                e.target.value,
                                            )
                                        }
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">All branches</option>
                                        {branches.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <Button type="submit" disabled={processing}>
                                    Apply
                                </Button>
                                <Link
                                    href={registerSessions.url()}
                                    className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                >
                                    Reset
                                </Link>
                            </>
                        )}
                    </Form>
                </div>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="mb-3 text-lg font-semibold">Sessions</h2>
                    {sessions.data.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No register sessions found.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="pb-2">Register</th>
                                        <th className="pb-2">Branch</th>
                                        <th className="pb-2">Opened</th>
                                        <th className="pb-2">Closed</th>
                                        <th className="pb-2 text-right">
                                            Opening
                                        </th>
                                        <th className="pb-2 text-right">
                                            Closing
                                        </th>
                                        <th className="pb-2 text-right">
                                            Variance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {sessions.data.map((s) => (
                                        <tr key={s.id}>
                                            <td className="py-2">
                                                {s.register?.name ?? '-'}
                                            </td>
                                            <td className="py-2">
                                                {s.branch?.name ?? '-'}
                                            </td>
                                            <td className="py-2">
                                                {s.opened_at}
                                            </td>
                                            <td className="py-2">
                                                {s.closed_at ?? '-'}
                                            </td>
                                            <td className="py-2 text-right">
                                                {Number(
                                                    s.opening_balance,
                                                ).toFixed(2)}
                                            </td>
                                            <td className="py-2 text-right">
                                                {s.closing_balance
                                                    ? Number(
                                                          s.closing_balance,
                                                      ).toFixed(2)
                                                    : '-'}
                                            </td>
                                            <td className="py-2 text-right">
                                                {s.variance !== null
                                                    ? Number(
                                                          s.variance,
                                                      ).toFixed(2)
                                                    : '-'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

RegisterSessionsReportPage.layout = {
    breadcrumbs: [
        {
            title: 'Reports',
            href: '/reports/register-sessions',
        },
        {
            title: 'Register sessions',
            href: '/reports/register-sessions',
        },
    ],
};
