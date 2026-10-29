import { Form, useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Input, Label } from '@/components/ui';

export default function ProductBrandCreatePage() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('product-brands.store'));
    };

    return (
        <>
            <Head title="Create product brand" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create product brand"
                    description="Add a new product brand to your catalog."
                />

                <Form onSubmit={handleSubmit}>
                    <div className="rounded-xl border bg-card p-6 shadow-sm space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Enter brand name"
                                className={errors.name ? 'border-red-500' : ''}
                            />
                            {errors.name && (
                                <p className="text-sm text-red-500">{errors.name}</p>
                            )}
                        </div>

                        <div className="flex justify-end space-x-2">
                            <button
                                type="button"
                                onClick={() => window.history.back()}
                                className="rounded-md border bg-background px-4 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            >
                                {processing ? 'Creating...' : 'Create'}
                            </button>
                        </div>
                    </div>
                </Form>
            </div>
        </>
    );
}

ProductBrandCreatePage.layout = {
    breadcrumbs: [
        {
            title: 'Product brands',
            href: '/product-brands',
        },
        {
            title: 'Create',
            href: '/product-brands/create',
        },
    ],
};