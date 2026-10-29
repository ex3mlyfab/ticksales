import { Form, useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Input, Label, Textarea, Select, Checkbox } from '@/components/ui';

interface ProductCategory {
    id: string;
    name: string;
}

interface Props {
    categories: ProductCategory[];
}

export default function ProductCategoryCreatePage({ categories }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        description: '',
        image_path: '',
        sort_order: 0,
        is_active: true,
        parent_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('product-categories.store'));
    };

    return (
        <>
            <Head title="Create product category" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create product category"
                    description="Add a new product category to your catalog."
                />

                <Form onSubmit={handleSubmit}>
                    <div className="rounded-xl border bg-card p-6 shadow-sm space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Enter category name"
                                className={errors.name ? 'border-red-500' : ''}
                            />
                            {errors.name && (
                                <p className="text-sm text-red-500">{errors.name}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                                placeholder="Enter slug"
                                className={errors.slug ? 'border-red-500' : ''}
                            />
                            {errors.slug && (
                                <p className="text-sm text-red-500">{errors.slug}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="parent_id">Parent Category</Label>
                            <Select
                                value={data.parent_id}
                                onValueChange={(value) => setData('parent_id', value)}
                                options={[
                                    { value: '', label: 'None (Top-level)' },
                                    ...categories.map((c) => ({
                                        value: c.id,
                                        label: c.name,
                                    })),
                                ]}
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="Enter description"
                                rows={3}
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="image_path">Image Path</Label>
                            <Input
                                id="image_path"
                                value={data.image_path}
                                onChange={(e) => setData('image_path', e.target.value)}
                                placeholder="/images/category.jpg"
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="sort_order">Sort Order</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                value={data.sort_order}
                                onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)}
                                min={0}
                            />
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) => setData('is_active', checked)}
                            />
                            <Label htmlFor="is_active">Active</Label>
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

ProductCategoryCreatePage.layout = {
    breadcrumbs: [
        {
            title: 'Product categories',
            href: '/product-categories',
        },
        {
            title: 'Create',
            href: '/product-categories/create',
        },
    ],
};