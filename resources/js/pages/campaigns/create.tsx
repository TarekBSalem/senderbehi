import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Template {
    id: number;
    name: string;
    slug: string;
    description: string;
    category: string;
}

interface Props {
    templates: Template[];
    selectedTemplateId?: number;
}

export default function CampaignCreate({ templates, selectedTemplateId }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        subject: '',
        template_id: selectedTemplateId?.toString() || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/campaigns');
    };

    return (
        <>
            <Head title="Create Campaign" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-bold">Create Campaign</h1>
                    <p className="text-muted-foreground">Set up your new email campaign</p>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Campaign Details</CardTitle>
                        <CardDescription>Fill in the details for your campaign</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Campaign Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g., Summer Sale 2026"
                                />
                                {errors.name && (
                                    <p className="text-sm text-red-500">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="subject">Email Subject</Label>
                                <Input
                                    id="subject"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="e.g., Don't miss our summer deals!"
                                />
                                {errors.subject && (
                                    <p className="text-sm text-red-500">{errors.subject}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="template">Template</Label>
                                <Select
                                    value={data.template_id}
                                    onValueChange={(value) => setData('template_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a template" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {templates.map((template) => (
                                            <SelectItem key={template.id} value={template.id.toString()}>
                                                {template.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.template_id && (
                                    <p className="text-sm text-red-500">{errors.template_id}</p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2 pt-4">
                                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating...' : 'Create Campaign'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CampaignCreate.layout = {
    breadcrumbs: [
        { title: 'Campaigns', href: '/campaigns' },
        { title: 'Create', href: '/campaigns/create' },
    ],
};
