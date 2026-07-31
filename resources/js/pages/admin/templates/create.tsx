import { Head, Link, router } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Category {
    id: number;
    name: string;
}

interface Template {
    id: number;
    name: string;
    slug: string;
    subject: string;
    body_html: string;
}

interface Props {
    categories: Category[];
    templates: Template[];
}

const steps = ['Choose a start', 'Template details'];

export default function AdminTemplatesCreate({ categories, templates }: Props) {
    const [step, setStep] = useState(0);
    const [source, setSource] = useState<'template' | 'html'>('template');
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [subject, setSubject] = useState('');
    const [bodyHtml, setBodyHtml] = useState('');
    const [categoryId, setCategoryId] = useState<string>('');
    const [isActive, setIsActive] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const autoSlug = (value: string) => {
        if (!slug || slug === name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')) {
            setSlug(value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, ''));
        }
    };

    const pickTemplate = (template: Template) => {
        setSource('template');
        setName(`${template.name} (Copy)`);
        setSlug('');
        setSubject(template.subject);
        setBodyHtml(template.body_html);
    };

    const next = () => {
        setErrors({});
        setStep(1);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        router.post('/admin/templates', {
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

    const canContinue = source === 'template' || Boolean(bodyHtml.trim());

    return (
        <>
            <Head title="Create Template" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <Link href="/admin/templates" className="text-sm text-muted-foreground hover:underline">
                        &larr; Back to templates
                    </Link>
                    <h1 className="text-2xl font-bold mt-2">Create Template</h1>
                </div>

                <div className="flex items-center gap-2">
                    {steps.map((title, index) => (
                        <div key={title} className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => index < step && setStep(index)}
                                className={`flex h-7 w-7 items-center justify-center rounded-full text-sm font-medium transition-colors ${
                                    index < step
                                        ? 'bg-primary text-primary-foreground'
                                        : index === step
                                          ? 'bg-primary/10 text-primary ring-1 ring-primary'
                                          : 'bg-muted text-muted-foreground'
                                }`}
                            >
                                {index < step ? <Check className="h-4 w-4" /> : index + 1}
                            </button>
                            <span
                                className={`text-sm ${index === step ? 'font-medium' : 'text-muted-foreground'}`}
                            >
                                {title}
                            </span>
                            {index < steps.length - 1 && <div className="mx-2 h-px w-6 bg-border" />}
                        </div>
                    ))}
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{steps[step]}</CardTitle>
                        <CardDescription>
                            {step === 0
                                ? 'Start from an existing template or import your own HTML'
                                : 'Fill in the details for your new template'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {step === 0 && (
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setSource('template')}
                                        className={`rounded-md border p-3 text-left text-sm transition-colors ${
                                            source === 'template'
                                                ? 'border-primary bg-primary/5'
                                                : 'hover:bg-accent'
                                        }`}
                                    >
                                        <span className="font-medium">From existing template</span>
                                        <span className="mt-1 block text-muted-foreground">
                                            Copy an existing template as a starting point
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setSource('html')}
                                        className={`rounded-md border p-3 text-left text-sm transition-colors ${
                                            source === 'html'
                                                ? 'border-primary bg-primary/5'
                                                : 'hover:bg-accent'
                                        }`}
                                    >
                                        <span className="font-medium">Import HTML</span>
                                        <span className="mt-1 block text-muted-foreground">
                                            Paste your own HTML email body
                                        </span>
                                    </button>
                                </div>

                                {source === 'template' ? (
                                    <div className="space-y-2">
                                        <Label>Choose a template</Label>
                                        <div className="max-h-64 space-y-2 overflow-y-auto rounded-md border p-2">
                                            {templates.map((template) => (
                                                <button
                                                    key={template.id}
                                                    type="button"
                                                    onClick={() => pickTemplate(template)}
                                                    className="flex w-full items-center justify-between rounded-md border px-3 py-2 text-left text-sm transition-colors hover:bg-accent"
                                                >
                                                    <div>
                                                        <span className="font-medium">{template.name}</span>
                                                        <span className="ml-2 text-muted-foreground">
                                                            {template.subject}
                                                        </span>
                                                    </div>
                                                    <span className="text-muted-foreground">
                                                        {template.slug}
                                                    </span>
                                                </button>
                                            ))}
                                            {templates.length === 0 && (
                                                <p className="py-6 text-center text-sm text-muted-foreground">
                                                    No templates yet. Switch to "Import HTML".
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        <Label htmlFor="importHtml">HTML Body</Label>
                                        <textarea
                                            id="importHtml"
                                            rows={14}
                                            value={bodyHtml}
                                            onChange={(e) => setBodyHtml(e.target.value)}
                                            placeholder="<h1>Hello {{name}}!</h1><p>Your template body...</p>"
                                            className={`flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm font-mono shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring ${errors.body_html ? 'border-destructive' : ''}`}
                                        />
                                        {errors.body_html && (
                                            <p className="text-sm text-destructive">{errors.body_html}</p>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        {step === 1 && (
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

                                <div className="space-y-2">
                                    <Label htmlFor="subject">Default Subject</Label>
                                    <Input
                                        id="subject"
                                        value={subject}
                                        onChange={(e) => setSubject(e.target.value)}
                                        className={errors.subject ? 'border-destructive' : ''}
                                    />
                                    {errors.subject && (
                                        <p className="text-sm text-destructive">{errors.subject}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="category">Category</Label>
                                    <Select value={categoryId} onValueChange={setCategoryId}>
                                        <SelectTrigger id="category" className="w-full">
                                            <SelectValue placeholder="Select a category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((cat) => (
                                                <SelectItem key={cat.id} value={String(cat.id)}>
                                                    {cat.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.category_id && (
                                        <p className="text-sm text-destructive">{errors.category_id}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="bodyHtml">HTML Body</Label>
                                    <textarea
                                        id="bodyHtml"
                                        rows={14}
                                        value={bodyHtml}
                                        onChange={(e) => setBodyHtml(e.target.value)}
                                        className={`flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm font-mono shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring ${errors.body_html ? 'border-destructive' : ''}`}
                                    />
                                    {errors.body_html && (
                                        <p className="text-sm text-destructive">{errors.body_html}</p>
                                    )}
                                </div>

                                <div className="flex items-center gap-3 pt-2">
                                    <Checkbox
                                        id="isActive"
                                        checked={isActive}
                                        onCheckedChange={(v) => setIsActive(v === true)}
                                    />
                                    <Label htmlFor="isActive">Active</Label>
                                </div>
                            </form>
                        )}
                    </CardContent>
                    <CardFooter className="justify-between border-t px-6 py-4">
                        <div>
                            {step > 0 && (
                                <Button type="button" variant="outline" onClick={() => setStep(0)}>
                                    <ChevronLeft className="h-4 w-4 mr-1" />
                                    Back
                                </Button>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <Link href="/admin/templates">
                                <Button type="button" variant="ghost">Cancel</Button>
                            </Link>
                            {step === 0 ? (
                                <Button type="button" onClick={next} disabled={!canContinue}>
                                    Continue
                                    <ChevronRight className="h-4 w-4 ml-1" />
                                </Button>
                            ) : (
                                <Button type="submit" onClick={submit} disabled={submitting}>
                                    {submitting ? 'Creating...' : 'Create Template'}
                                </Button>
                            )}
                        </div>
                    </CardFooter>
                </Card>
            </div>
        </>
    );
}

AdminTemplatesCreate.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Templates', href: '/admin/templates' },
        { title: 'Create', href: '' },
    ],
};
