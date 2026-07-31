import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface Template {
    id: number;
    name: string;
    slug: string;
    description: string;
    category: { id: number; name: string; slug: string } | null;
    preview_image: string | null;
}

interface Props {
    templates: Template[];
    categories: { id: number; name: string; slug: string }[];
}

export default function TemplateIndex({ templates, categories }: Props) {
    return (
        <>
            <Head title="Templates" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Email Templates</h1>
                        <p className="text-muted-foreground">Choose a template for your campaign</p>
                    </div>
                </div>

                <div className="flex gap-2 flex-wrap">
                    <Badge variant="outline" className="cursor-pointer">All</Badge>
                    {categories.map((category) => (
                        <Badge key={category.slug} variant="outline" className="cursor-pointer capitalize">
                            {category.name}
                        </Badge>
                    ))}
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {templates.map((template) => (
                        <Card key={template.id} className="overflow-hidden">
                            <div className="aspect-video bg-muted flex items-center justify-center">
                                <span className="text-4xl">📧</span>
                            </div>
                            <CardHeader>
                                <CardTitle>{template.name}</CardTitle>
                                <CardDescription>{template.description}</CardDescription>
                            </CardHeader>
                            <CardFooter className="flex justify-between">
                                <Badge variant="secondary" className="capitalize">
                                    {template.category?.name}
                                </Badge>
                                <Link href={`/templates/${template.slug}`}>
                                    <Button variant="outline" size="sm">Preview</Button>
                                </Link>
                            </CardFooter>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

TemplateIndex.layout = {
    breadcrumbs: [
        { title: 'Templates', href: '/templates' },
    ],
};
