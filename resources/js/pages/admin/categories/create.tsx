import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { useState } from 'react';

export default function AdminCategoriesCreate() {
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [isActive, setIsActive] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const autoSlug = (value: string) => {
        if (!slug || slug === name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')) {
            setSlug(value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, ''));
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        router.post('/admin/categories', {
            name,
            slug,
            is_active: isActive,
        }, {
            onError: (errs) => setErrors(errs),
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <>
            <Head title="Create Category" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <Link href="/admin/categories" className="text-sm text-muted-foreground hover:underline">
                        &larr; Back to categories
                    </Link>
                    <h1 className="text-2xl font-bold mt-2">Create Category</h1>
                </div>

                <Card className="max-w-lg">
                    <CardHeader>
                        <CardTitle>New Category</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={name}
                                    onChange={(e) => {
                                        setName(e.target.value);
                                        autoSlug(e.target.value);
                                    }}
                                    className={errors.name ? 'border-destructive' : ''}
                                />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    value={slug}
                                    onChange={(e) => setSlug(e.target.value)}
                                    className={errors.slug ? 'border-destructive' : ''}
                                />
                                {errors.slug && <p className="text-sm text-destructive">{errors.slug}</p>}
                            </div>

                            <div className="flex items-center gap-3 pt-2">
                                <Checkbox id="isActive" checked={isActive} onCheckedChange={(v) => setIsActive(v === true)} />
                                <Label htmlFor="isActive">Active</Label>
                            </div>

                            <div className="flex gap-3 pt-2">
                                <Button type="submit" disabled={submitting}>
                                    {submitting ? 'Creating...' : 'Create Category'}
                                </Button>
                                <Link href="/admin/categories">
                                    <Button type="button" variant="outline">Cancel</Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminCategoriesCreate.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Categories', href: '/admin/categories' },
        { title: 'Create', href: '' },
    ],
};
