import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Template {
    id: number;
    name: string;
    slug: string;
    description: string;
    category: { id: number; name: string; slug: string } | null;
    subject: string;
    body_html: string;
}

interface Props {
    template: Template;
}

export default function TemplateShow({ template }: Props) {
    return (
        <>
            <Head title={template.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold">{template.name}</h1>
                            <Badge variant="secondary" className="capitalize">{template.category?.name}</Badge>
                        </div>
                        <p className="text-muted-foreground">{template.description}</p>
                    </div>
                    <Link href={`/campaigns/create?template=${template.id}`}>
                        <Button>Use This Template</Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Template Preview</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="border rounded-lg overflow-hidden">
                            <iframe
                                srcDoc={template.body_html}
                                className="w-full h-[600px]"
                                title="Template Preview"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

TemplateShow.layout = {
    breadcrumbs: [
        { title: 'Templates', href: '/templates' },
    ],
};
