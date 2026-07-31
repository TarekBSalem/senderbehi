import { Head, useForm } from '@inertiajs/react';
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

interface Template {
    id: number;
    name: string;
    slug: string;
    body_html: string;
}

interface CampaignData {
    id: number;
    name: string;
    subject: string;
    template_id: number | null;
    body_html: string | null;
    scheduled_at: string | null;
    sends_per_minute: number | null;
    use_custom_smtp: boolean;
    smtp_host: string | null;
    smtp_port: number | null;
    smtp_username: string | null;
    smtp_encryption: string | null;
    smtp_from_email: string | null;
    smtp_from_name: string | null;
}

interface Props {
    templates: Template[];
    campaign?: CampaignData;
    selectedTemplateId?: number;
}

const steps = ['Details', 'Content', 'Sending', 'Review'];

const encryptionOptions = [
    { value: 'tls', label: 'TLS' },
    { value: 'ssl', label: 'SSL' },
    { value: 'none', label: 'None' },
];

const toDatetimeLocal = (value: string | null): string => {
    if (!value) {
return '';
}

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
return '';
}

    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

export default function CampaignCreate({ templates, campaign, selectedTemplateId }: Props) {
    const isEdit = Boolean(campaign);
    const initialTemplateId = campaign?.template_id?.toString() || selectedTemplateId?.toString() || '';

    const { data, setData, post, put, transform, processing, errors } = useForm({
        name: campaign?.name || '',
        subject: campaign?.subject || '',
        template_id: initialTemplateId,
        body_html: campaign?.body_html || '',
        scheduled_at: toDatetimeLocal(campaign?.scheduled_at || null),
        sends_per_minute: campaign?.sends_per_minute?.toString() || '',
        use_custom_smtp: campaign?.use_custom_smtp || false,
        smtp_host: campaign?.smtp_host || '',
        smtp_port: campaign?.smtp_port?.toString() || '',
        smtp_username: campaign?.smtp_username || '',
        smtp_password: '',
        smtp_encryption: campaign?.smtp_encryption || 'tls',
        smtp_from_email: campaign?.smtp_from_email || '',
        smtp_from_name: campaign?.smtp_from_name || '',
    });

    const [step, setStep] = useState(0);
    const [contentMode, setContentMode] = useState<'template' | 'html'>(
        !campaign?.template_id && campaign?.body_html ? 'html' : 'template'
    );

    const bodyHtml =
        contentMode === 'html'
            ? data.body_html
            : templates.find((t) => t.id.toString() === data.template_id)?.body_html || '';

    const previewHtml =
        bodyHtml
            ? `<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="margin:0;padding:0;font-family:system-ui,sans-serif">${bodyHtml}</body></html>`
            : null;

    const next = () => {
        if (step < steps.length - 1) {
            setStep(step + 1);
        }
    };

    const back = () => {
        if (step > 0) {
            setStep(step - 1);
        }
    };

    const handleError = (errs: Record<string, string>) => {
        const keys = Object.keys(errs);

        if (keys.some((key) => ['name', 'subject'].includes(key))) {
            setStep(0);
        } else if (keys.some((key) => ['template_id', 'body_html'].includes(key))) {
            setStep(1);
        } else if (keys.length > 0) {
            setStep(2);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        transform((formData) => ({
            ...formData,
            template_id: contentMode === 'template' ? formData.template_id : '',
            body_html: contentMode === 'html' ? formData.body_html : '',
            smtp_port: formData.smtp_port ? Number(formData.smtp_port) : null,
            sends_per_minute: formData.sends_per_minute ? Number(formData.sends_per_minute) : null,
        }));

        if (isEdit && campaign) {
            put(`/campaigns/${campaign.id}`, { onError: handleError });
        } else {
            post('/campaigns', { onError: handleError });
        }
    };

    const canContinue = () => {
        if (step === 0) {
            return Boolean(data.name.trim() && data.subject.trim());
        }

        if (step === 1) {
            if (contentMode === 'template') {
                return Boolean(data.template_id);
            }

            return Boolean(data.body_html.trim());
        }

        if (step === 2) {
            if (!data.use_custom_smtp) {
                return true;
            }

            return Boolean(data.smtp_host && data.smtp_port && data.smtp_from_email);
        }

        return true;
    };

    const selectedTemplate = templates.find((t) => t.id.toString() === data.template_id);

    return (
        <>
            <Head title={isEdit ? 'Edit Campaign' : 'Create Campaign'} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-bold">{isEdit ? 'Edit Campaign' : 'Create Campaign'}</h1>
                    <p className="text-muted-foreground">
                        {isEdit ? 'Update the details of your campaign' : 'Set up your new email campaign'}
                    </p>
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
                            {index < steps.length - 1 && (
                                <div className="mx-2 h-px w-6 bg-border" />
                            )}
                        </div>
                    ))}
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{steps[step]}</CardTitle>
                        <CardDescription>
                            {step === 0 && 'Fill in the basic details for your campaign'}
                            {step === 1 && 'Choose a template or import your own HTML'}
                            {step === 2 && 'Configure scheduling, send speed and SMTP settings'}
                            {step === 3 && 'Review your campaign before saving'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {step === 0 && (
                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Campaign Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="e.g., Summer Sale 2026"
                                    />
                                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="subject">Email Subject</Label>
                                    <Input
                                        id="subject"
                                        value={data.subject}
                                        onChange={(e) => setData('subject', e.target.value)}
                                        placeholder="e.g., Don't miss our summer deals!"
                                    />
                                    {errors.subject && <p className="text-sm text-red-500">{errors.subject}</p>}
                                </div>
                            </div>
                        )}

                        {step === 1 && (
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setContentMode('template')}
                                        className={`rounded-md border p-3 text-left text-sm transition-colors ${
                                            contentMode === 'template'
                                                ? 'border-primary bg-primary/5'
                                                : 'hover:bg-accent'
                                        }`}
                                    >
                                        <span className="font-medium">Use a Template</span>
                                        <span className="mt-1 block text-muted-foreground">
                                            Start from one of the curated templates
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setContentMode('html')}
                                        className={`rounded-md border p-3 text-left text-sm transition-colors ${
                                            contentMode === 'html'
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

                                {contentMode === 'template' ? (
                                    <div className="space-y-2">
                                        <Label htmlFor="template">Template</Label>
                                        <Select
                                            value={data.template_id}
                                            onValueChange={(value) => setData('template_id', value)}
                                        >
                                            <SelectTrigger id="template" className="w-full">
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
                                ) : (
                                    <div className="space-y-2">
                                        <Label htmlFor="bodyHtml">HTML Body</Label>
                                        <textarea
                                            id="bodyHtml"
                                            rows={12}
                                            value={data.body_html}
                                            onChange={(e) => setData('body_html', e.target.value)}
                                            placeholder="<h1>Hello {{name}}!</h1><p>Your campaign body...</p>"
                                            className={`flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm font-mono shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring ${errors.body_html ? 'border-destructive' : ''}`}
                                        />
                                        {errors.body_html && (
                                            <p className="text-sm text-red-500">{errors.body_html}</p>
                                        )}
                                    </div>
                                )}

                                {previewHtml && (
                                    <div className="space-y-2">
                                        <Label>Preview</Label>
                                        <iframe
                                            srcDoc={previewHtml}
                                            className="w-full rounded border bg-white"
                                            style={{ height: '300px' }}
                                            title="Email preview"
                                        />
                                    </div>
                                )}

                                {contentMode === 'template' && selectedTemplate && (
                                    <p className="text-sm text-muted-foreground">
                                        Template: <span className="font-medium">{selectedTemplate.name}</span>
                                    </p>
                                )}
                            </div>
                        )}

                        {step === 2 && (
                            <div className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="scheduled-at">Schedule (Optional)</Label>
                                    <Input
                                        id="scheduled-at"
                                        type="datetime-local"
                                        value={data.scheduled_at}
                                        onChange={(e) => setData('scheduled_at', e.target.value)}
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Leave empty to send manually. Emails go out at this time.
                                    </p>
                                    {errors.scheduled_at && (
                                        <p className="text-sm text-red-500">{errors.scheduled_at}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="sends-per-minute">Send Speed (emails per minute)</Label>
                                    <Input
                                        id="sends-per-minute"
                                        type="number"
                                        min={1}
                                        max={600}
                                        value={data.sends_per_minute}
                                        onChange={(e) => setData('sends_per_minute', e.target.value)}
                                        placeholder="Leave empty to use your account default"
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Controls how fast the campaign is delivered.
                                    </p>
                                    {errors.sends_per_minute && (
                                        <p className="text-sm text-red-500">{errors.sends_per_minute}</p>
                                    )}
                                </div>

                                <div className="flex items-center gap-3 rounded-md border p-3">
                                    <Checkbox
                                        id="use-custom-smtp"
                                        checked={data.use_custom_smtp}
                                        onCheckedChange={(v) => setData('use_custom_smtp', v === true)}
                                    />
                                    <div>
                                        <Label htmlFor="use-custom-smtp" className="font-medium">
                                            Use custom SMTP for this campaign
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            Send through your own email server instead of the platform.
                                        </p>
                                    </div>
                                </div>

                                {data.use_custom_smtp && (
                                    <div className="grid gap-4 rounded-md border p-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="smtp-host">SMTP Host</Label>
                                            <Input
                                                id="smtp-host"
                                                value={data.smtp_host}
                                                onChange={(e) => setData('smtp_host', e.target.value)}
                                                placeholder="smtp.example.com"
                                            />
                                            {errors.smtp_host && (
                                                <p className="text-sm text-red-500">{errors.smtp_host}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="smtp-port">Port</Label>
                                            <Input
                                                id="smtp-port"
                                                type="number"
                                                value={data.smtp_port}
                                                onChange={(e) => setData('smtp_port', e.target.value)}
                                                placeholder="587"
                                            />
                                            {errors.smtp_port && (
                                                <p className="text-sm text-red-500">{errors.smtp_port}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="smtp-username">Username</Label>
                                            <Input
                                                id="smtp-username"
                                                value={data.smtp_username}
                                                onChange={(e) => setData('smtp_username', e.target.value)}
                                                placeholder="apikey"
                                            />
                                            {errors.smtp_username && (
                                                <p className="text-sm text-red-500">{errors.smtp_username}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="smtp-password">Password</Label>
                                            <Input
                                                id="smtp-password"
                                                type="password"
                                                value={data.smtp_password}
                                                onChange={(e) => setData('smtp_password', e.target.value)}
                                                placeholder={isEdit ? 'Leave blank to keep current' : '••••••••'}
                                            />
                                            {errors.smtp_password && (
                                                <p className="text-sm text-red-500">{errors.smtp_password}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="smtp-encryption">Encryption</Label>
                                            <Select
                                                value={data.smtp_encryption}
                                                onValueChange={(value) => setData('smtp_encryption', value)}
                                            >
                                                <SelectTrigger id="smtp-encryption" className="w-full">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {encryptionOptions.map((option) => (
                                                        <SelectItem key={option.value} value={option.value}>
                                                            {option.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="smtp-from-email">From Email</Label>
                                            <Input
                                                id="smtp-from-email"
                                                type="email"
                                                value={data.smtp_from_email}
                                                onChange={(e) => setData('smtp_from_email', e.target.value)}
                                                placeholder="news@example.com"
                                            />
                                            {errors.smtp_from_email && (
                                                <p className="text-sm text-red-500">{errors.smtp_from_email}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2 sm:col-span-2">
                                            <Label htmlFor="smtp-from-name">From Name</Label>
                                            <Input
                                                id="smtp-from-name"
                                                value={data.smtp_from_name}
                                                onChange={(e) => setData('smtp_from_name', e.target.value)}
                                                placeholder="Acme Inc"
                                            />
                                            {errors.smtp_from_name && (
                                                <p className="text-sm text-red-500">{errors.smtp_from_name}</p>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {step === 3 && (
                            <dl className="space-y-4">
                                <div className="grid gap-2 rounded-md border p-4 sm:grid-cols-2">
                                    <div>
                                        <dt className="text-sm text-muted-foreground">Name</dt>
                                        <dd className="font-medium">{data.name || '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-muted-foreground">Subject</dt>
                                        <dd className="font-medium">{data.subject || '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-muted-foreground">Content</dt>
                                        <dd className="font-medium">
                                            {contentMode === 'template'
                                                ? selectedTemplate?.name || 'No template selected'
                                                : 'Custom HTML'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-muted-foreground">Schedule</dt>
                                        <dd className="font-medium">
                                            {data.scheduled_at
                                                ? new Date(data.scheduled_at).toLocaleString()
                                                : 'Send manually'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-muted-foreground">Send Speed</dt>
                                        <dd className="font-medium">
                                            {data.sends_per_minute
                                                ? `${data.sends_per_minute} emails/min`
                                                : 'Account default'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-muted-foreground">SMTP</dt>
                                        <dd className="font-medium">
                                            {data.use_custom_smtp && data.smtp_host
                                                ? `${data.smtp_host}:${data.smtp_port || ''}`
                                                : 'Platform default'}
                                        </dd>
                                    </div>
                                </div>

                                {errors.body_html && (
                                    <p className="text-sm text-red-500">{errors.body_html}</p>
                                )}
                            </dl>
                        )}
                    </CardContent>
                    <CardFooter className="justify-between border-t px-6 py-4">
                        <div>
                            {step > 0 && (
                                <Button type="button" variant="outline" onClick={back}>
                                    <ChevronLeft className="h-4 w-4 mr-1" />
                                    Back
                                </Button>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </Button>
                            {step < steps.length - 1 ? (
                                <Button type="button" onClick={next} disabled={!canContinue()}>
                                    Continue
                                    <ChevronRight className="h-4 w-4 ml-1" />
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    onClick={handleSubmit}
                                    disabled={processing}
                                >
                                    {processing
                                        ? isEdit
                                            ? 'Saving...'
                                            : 'Creating...'
                                        : isEdit
                                          ? 'Save Campaign'
                                          : 'Create Campaign'}
                                </Button>
                            )}
                        </div>
                    </CardFooter>
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
