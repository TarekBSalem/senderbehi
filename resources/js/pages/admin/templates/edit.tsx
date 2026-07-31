import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useState } from 'react';

interface Template {
    id: number;
    name: string;
    slug: string;
    subject: string;
    body_html: string;
    category_id: number | null;
    thumbnail: string | null;
    variables: string[] | null;
    is_active: boolean;
    created_at: string;
}

interface Category {
    id: number;
    name: string;
}

interface Props {
    template: Template;
    categories: Category[];
}

export default function AdminTemplatesEdit({ template, categories }: Props) {
    const [name, setName] = useState(template.name);
    const [slug, setSlug] = useState(template.slug);
    const [subject, setSubject] = useState(template.subject);
    const [bodyHtml, setBodyHtml] = useState(template.body_html);
    const [categoryId, setCategoryId] = useState<string>(String(template.category_id ?? ''));
    const [isActive, setIsActive] = useState(template.is_active);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        router.put(`/admin/templates/${template.id}`, {
            name,
            slug,
            subject,
            body_html: bodyHtml,
            category_id: categoryId ? Number(categoryId) : null,
            is_active: isActive,
        }, {
            onError: (errs) => setErrors(errs),
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <>
            <Head title={`Edit ${template.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <Link href="/admin/templates" className="text-sm text-muted-foreground hover:underline">
                        &larr; Back to templates
                    </Link>
                    <h1 className="text-2xl font-bold mt-2">Edit Template</h1>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{template.name}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
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

                            <div className="space-y-2">
                                <Label htmlFor="subject">Default Subject</Label>
                                <Input
                                    id="subject"
                                    value={subject}
                                    onChange={(e) => setSubject(e.target.value)}
                                    className={errors.subject ? 'border-destructive' : ''}
                                />
                                {errors.subject && <p className="text-sm text-destructive">{errors.subject}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="category">Category</Label>
                                <Select value={categoryId} onValueChange={setCategoryId}>
                                    <SelectTrigger id="category" className="w-full">
                                        <SelectValue placeholder="Select a category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((cat) => (
                                            <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.category_id && <p className="text-sm text-destructive">{errors.category_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="bodyHtml">HTML Body</Label>
                                <textarea
                                    id="bodyHtml"
                                    rows={16}
                                    value={bodyHtml}
                                    onChange={(e) => setBodyHtml(e.target.value)}
                                    className={`flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm font-mono placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring ${errors.body_html ? 'border-destructive' : ''}`}
                                />
                                {errors.body_html && <p className="text-sm text-destructive">{errors.body_html}</p>}
                            </div>

                            <div className="flex items-center gap-3 pt-2">
                                <Checkbox id="isActive" checked={isActive} onCheckedChange={(v) => setIsActive(v === true)} />
                                <Label htmlFor="isActive">Active</Label>
                            </div>

                            <div className="flex gap-3 pt-2">
                                <Button type="submit" disabled={submitting}>
                                    {submitting ? 'Saving...' : 'Save Changes'}
                                </Button>
                                <Link href="/admin/templates">
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

AdminTemplatesEdit.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Templates', href: '/admin/templates' },
        { title: 'Edit', href: '' },
    ],
};
