import { Head, Link, router, useForm } from '@inertiajs/react';
import { CalendarDays, Clock, Monitor, RotateCcw, Send, Smartphone } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import ConfirmDialog from '@/components/confirm-dialog';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ResultDialog from '@/components/result-dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface Recipient {
    id: number;
    email: string;
    name: string | null;
    status: string;
    attempt_count: number;
    success_count: number;
    sent_at: string | null;
    error_message: string | null;
}

interface ImportResult {
    added: number;
    duplicates: number;
    invalid: number;
}

interface Campaign {
    id: number;
    name: string;
    subject: string;
    status: string;
    sent_count: number;
    failed_count: number;
    total_recipients: number;
    scheduled_at: string | null;
    sends_per_minute: number | null;
    use_custom_smtp: boolean;
    smtp_host: string | null;
    smtp_port: number | null;
    smtp_username: string | null;
    smtp_encryption: string | null;
    smtp_from_email: string | null;
    smtp_from_name: string | null;
    created_at: string;
    template: {
        name: string;
        slug: string;
    };
    recipients: Recipient[];
}

interface Props {
    campaign: Campaign;
    previewHtml: string | null;
}

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    scheduled: 'bg-purple-100 text-purple-800',
    queued: 'bg-yellow-100 text-yellow-800',
    sending: 'bg-blue-100 text-blue-800',
    sent: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    partial: 'bg-orange-100 text-orange-800',
};

const recipientStatusColors: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-800',
    sent: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
};

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

const buildImportMessage = (result: ImportResult): string => {
    const lines = [`${result.added} recipient${result.added === 1 ? '' : 's'} added`];

    if (result.duplicates > 0) {
        lines.push(`${result.duplicates} duplicate${result.duplicates === 1 ? '' : 's'} skipped`);
    }

    if (result.invalid > 0) {
        lines.push(`${result.invalid} invalid row${result.invalid === 1 ? '' : 's'} skipped`);
    }

    return lines.join('\n');
};

function SendingSettings({ campaign }: { campaign: Campaign }) {
    const [editing, setEditing] = useState(false);
    const { data, setData, transform, put, processing, errors } = useForm({
        scheduled_at: toDatetimeLocal(campaign.scheduled_at),
        sends_per_minute: campaign.sends_per_minute?.toString() || '',
        use_custom_smtp: campaign.use_custom_smtp,
        smtp_host: campaign.smtp_host || '',
        smtp_port: campaign.smtp_port?.toString() || '',
        smtp_username: campaign.smtp_username || '',
        smtp_password: '',
        smtp_encryption: campaign.smtp_encryption || 'tls',
        smtp_from_email: campaign.smtp_from_email || '',
        smtp_from_name: campaign.smtp_from_name || '',
    });

    const save = () => {
        transform((formData) => ({
            ...formData,
            smtp_port: formData.smtp_port ? Number(formData.smtp_port) : null,
            sends_per_minute: formData.sends_per_minute ? Number(formData.sends_per_minute) : null,
        }));

        put(`/campaigns/${campaign.id}`, {
            onSuccess: () => setEditing(false),
        });
    };

    if (!editing) {
        return (
            <Card>
                <CardHeader className="flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Sending Settings</CardTitle>
                        <CardDescription>How and when this campaign is delivered</CardDescription>
                    </div>
                    <Button variant="outline" size="sm" onClick={() => setEditing(true)}>
                        Edit Settings
                    </Button>
                </CardHeader>
                <CardContent>
                    <dl className="grid gap-3 sm:grid-cols-3">
                        <div>
                            <dt className="text-sm text-muted-foreground">Schedule</dt>
                            <dd className="font-medium">
                                {campaign.scheduled_at
                                    ? new Date(campaign.scheduled_at).toLocaleString()
                                    : 'Send manually'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-muted-foreground">Send Speed</dt>
                            <dd className="font-medium">
                                {campaign.sends_per_minute
                                    ? `${campaign.sends_per_minute} emails/min`
                                    : 'Account default'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-sm text-muted-foreground">SMTP</dt>
                            <dd className="font-medium">
                                {campaign.use_custom_smtp && campaign.smtp_host
                                    ? `${campaign.smtp_host}:${campaign.smtp_port || ''}`
                                    : 'Platform default'}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Sending Settings</CardTitle>
                <CardDescription>Update how and when this campaign is delivered</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="space-y-2">
                    <Label htmlFor="settings-schedule">Schedule (Optional)</Label>
                    <Input
                        id="settings-schedule"
                        type="datetime-local"
                        value={data.scheduled_at}
                        onChange={(e) => setData('scheduled_at', e.target.value)}
                    />
                    <p className="text-sm text-muted-foreground">
                        Leave empty to send manually.
                    </p>
                    {errors.scheduled_at && <p className="text-sm text-red-500">{errors.scheduled_at}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="settings-speed">Send Speed (emails per minute)</Label>
                    <Input
                        id="settings-speed"
                        type="number"
                        min={1}
                        max={600}
                        value={data.sends_per_minute}
                        onChange={(e) => setData('sends_per_minute', e.target.value)}
                        placeholder="Leave empty to use your account default"
                    />
                    {errors.sends_per_minute && (
                        <p className="text-sm text-red-500">{errors.sends_per_minute}</p>
                    )}
                </div>

                <div className="flex items-center gap-3 rounded-md border p-3">
                    <Checkbox
                        id="settings-custom-smtp"
                        checked={data.use_custom_smtp}
                        onCheckedChange={(v) => setData('use_custom_smtp', v === true)}
                    />
                    <div>
                        <Label htmlFor="settings-custom-smtp" className="font-medium">
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
                            <Label htmlFor="settings-smtp-host">SMTP Host</Label>
                            <Input
                                id="settings-smtp-host"
                                value={data.smtp_host}
                                onChange={(e) => setData('smtp_host', e.target.value)}
                                placeholder="smtp.example.com"
                            />
                            {errors.smtp_host && <p className="text-sm text-red-500">{errors.smtp_host}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="settings-smtp-port">Port</Label>
                            <Input
                                id="settings-smtp-port"
                                type="number"
                                value={data.smtp_port}
                                onChange={(e) => setData('smtp_port', e.target.value)}
                                placeholder="587"
                            />
                            {errors.smtp_port && <p className="text-sm text-red-500">{errors.smtp_port}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="settings-smtp-username">Username</Label>
                            <Input
                                id="settings-smtp-username"
                                value={data.smtp_username}
                                onChange={(e) => setData('smtp_username', e.target.value)}
                                placeholder="apikey"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="settings-smtp-password">Password</Label>
                            <Input
                                id="settings-smtp-password"
                                type="password"
                                value={data.smtp_password}
                                onChange={(e) => setData('smtp_password', e.target.value)}
                                placeholder="Leave blank to keep current"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="settings-smtp-encryption">Encryption</Label>
                            <Select
                                value={data.smtp_encryption}
                                onValueChange={(value) => setData('smtp_encryption', value)}
                            >
                                <SelectTrigger id="settings-smtp-encryption" className="w-full">
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
                            <Label htmlFor="settings-smtp-from-email">From Email</Label>
                            <Input
                                id="settings-smtp-from-email"
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
                            <Label htmlFor="settings-smtp-from-name">From Name</Label>
                            <Input
                                id="settings-smtp-from-name"
                                value={data.smtp_from_name}
                                onChange={(e) => setData('smtp_from_name', e.target.value)}
                                placeholder="Acme Inc"
                            />
                        </div>
                    </div>
                )}

                <div className="flex justify-end gap-2 pt-2">
                    <Button variant="outline" onClick={() => setEditing(false)}>
                        Cancel
                    </Button>
                    <Button onClick={save} disabled={processing}>
                        {processing ? 'Saving...' : 'Save Settings'}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

export default function CampaignShow({ campaign, previewHtml }: Props) {
    const [recipientEmail, setRecipientEmail] = useState('');
    const [recipientName, setRecipientName] = useState('');
    const [csvFile, setCsvFile] = useState<File | null>(null);
    const [scheduleOpen, setScheduleOpen] = useState(false);
    const [scheduleDate, setScheduleDate] = useState('');
    const [scheduleTime, setScheduleTime] = useState('');
    const [previewWidth, setPreviewWidth] = useState<'desktop' | 'mobile'>('desktop');
    const [importResult, setImportResult] = useState<ImportResult | null>(null);
    const [sendConfirmKind, setSendConfirmKind] = useState<'now' | 'scheduled' | null>(null);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [resendSelectedOpen, setResendSelectedOpen] = useState(false);
    const [resendAllOpen, setResendAllOpen] = useState(false);
    const [resendCampaignOpen, setResendCampaignOpen] = useState(false);
    const [resendRecipient, setResendRecipient] = useState<Recipient | null>(null);

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const result = flash?.importResult as ImportResult | undefined;

            if (result) {
                setImportResult(result);
            }
        });
    }, []);

    const handleAddRecipient = (e: React.FormEvent) => {
        e.preventDefault();

        if (!recipientEmail) {
return;
}

        router.post(`/campaigns/${campaign.id}/recipients`, {
            recipients: [{ email: recipientEmail, name: recipientName }],
        }, {
            onSuccess: () => {
                setRecipientEmail('');
                setRecipientName('');
            },
        });
    };

    const handleImportCsv = () => {
        if (!csvFile) {
return;
}

        const formData = new FormData();
        formData.append('file', csvFile);

        router.post(`/campaigns/${campaign.id}/recipients/import`, formData, {
            onSuccess: () => setCsvFile(null),
        });
    };

    const handleSendNow = () => {
        setSendConfirmKind('now');
    };

    const confirmSend = () => {
        const kind = sendConfirmKind;
        setSendConfirmKind(null);

        if (kind) {
            router.post(`/campaigns/${campaign.id}/send`);
        }
    };

    const handleSchedule = () => {
        const scheduledAt = `${scheduleDate} ${scheduleTime}`;
        router.put(`/campaigns/${campaign.id}`, {
            scheduled_at: scheduledAt,
        }, {
            onSuccess: () => {
                setScheduleOpen(false);
                router.reload();
            },
        });
    };

    const handleSendScheduled = () => {
        setSendConfirmKind('scheduled');
    };

    const resendableRecipients = campaign.recipients?.filter((recipient) => ['failed', 'sent'].includes(recipient.status)) ?? [];
    const failedRecipients = campaign.recipients?.filter((recipient) => recipient.status === 'failed') ?? [];
    const allResendableSelected = resendableRecipients.length > 0 && selectedIds.length === resendableRecipients.length;

    const toggleRecipient = (recipientId: number) => {
        setSelectedIds((prev) => (
            prev.includes(recipientId) ? prev.filter((id) => id !== recipientId) : [...prev, recipientId]
        ));
    };

    const toggleSelectAll = () => {
        setSelectedIds(allResendableSelected ? [] : resendableRecipients.map((recipient) => recipient.id));
    };

    const confirmResendSelected = () => {
        const ids = selectedIds;
        setResendSelectedOpen(false);
        setSelectedIds([]);

        if (ids.length > 0) {
            router.post(`/campaigns/${campaign.id}/recipients/resend`, {
                recipient_ids: ids,
            });
        }
    };

    const confirmResendAllFailed = () => {
        setResendAllOpen(false);
        router.post(`/campaigns/${campaign.id}/recipients/resend`);
    };

    const confirmResendCampaign = () => {
        const ids = campaign.recipients?.filter((recipient) => ['sent', 'failed'].includes(recipient.status)).map((recipient) => recipient.id) ?? [];
        setResendCampaignOpen(false);

        if (ids.length > 0) {
            router.post(`/campaigns/${campaign.id}/recipients/resend`, {
                recipient_ids: ids,
            });
        }
    };

    const confirmResendRecipient = () => {
        if (!resendRecipient) {
            return;
        }

        const id = resendRecipient.id;
        setResendRecipient(null);
        router.post(`/campaigns/${campaign.id}/recipients/resend`, {
            recipient_ids: [id],
        });
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this campaign?')) {
            router.delete(`/campaigns/${campaign.id}`);
        }
    };

    const handleRemoveRecipient = (recipientId: number) => {
        if (confirm('Remove this recipient?')) {
            router.delete(`/campaigns/${campaign.id}/recipients/${recipientId}`);
        }
    };

    return (
        <>
            <Head title={campaign.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold">{campaign.name}</h1>
                            <Badge className={statusColors[campaign.status]}>{campaign.status}</Badge>
                        </div>
                        <p className="text-muted-foreground">
                            Subject: {campaign.subject} | Template: {campaign.template?.name}
                        </p>
                        {campaign.scheduled_at && (
                            <p className="text-sm text-muted-foreground flex items-center gap-1 mt-1">
                                <CalendarDays className="h-3.5 w-3.5" />
                                Scheduled for: {new Date(campaign.scheduled_at).toLocaleString()}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        {campaign.status !== 'draft' && (
                            <Link
                                href={`/campaigns/${campaign.id}/analytics`}
                                className="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-xs hover:bg-accent hover:text-accent-foreground"
                            >
                                Analytics
                            </Link>
                        )}
                        {['sent', 'partial', 'failed'].includes(campaign.status) && failedRecipients.length > 0 && (
                            <Button variant="outline" onClick={() => setResendAllOpen(true)}>
                                <RotateCcw className="h-4 w-4 mr-2" />
                                Resend Failed ({failedRecipients.length})
                            </Button>
                        )}
                        {campaign.status === 'sent' && resendableRecipients.length > 0 && (
                            <Button variant="outline" onClick={() => setResendCampaignOpen(true)}>
                                <RotateCcw className="h-4 w-4 mr-2" />
                                Resend Campaign
                            </Button>
                        )}
                        {campaign.status === 'draft' && (
                            <>
                                <Link
                                    href={`/campaigns/${campaign.id}/edit`}
                                    className="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-xs hover:bg-accent hover:text-accent-foreground"
                                >
                                    Edit
                                </Link>
                                <Button onClick={handleSendNow}>
                                    <Send className="h-4 w-4 mr-2" />
                                    Send Now
                                </Button>
                                <Dialog open={scheduleOpen} onOpenChange={setScheduleOpen}>
                                    <DialogTrigger asChild>
                                        <Button variant="outline">
                                            <Clock className="h-4 w-4 mr-2" />
                                            Schedule
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Schedule Campaign</DialogTitle>
                                            <DialogDescription>
                                                Choose a date and time to send this campaign.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="space-y-4 py-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="schedule-date">Date</Label>
                                                <Input
                                                    id="schedule-date"
                                                    type="date"
                                                    value={scheduleDate}
                                                    onChange={(e) => setScheduleDate(e.target.value)}
                                                    min={new Date().toISOString().slice(0, 10)}
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="schedule-time">Time</Label>
                                                <Input
                                                    id="schedule-time"
                                                    type="time"
                                                    value={scheduleTime}
                                                    onChange={(e) => setScheduleTime(e.target.value)}
                                                />
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setScheduleOpen(false)}>
                                                Cancel
                                            </Button>
                                            <Button onClick={handleSchedule} disabled={!scheduleDate || !scheduleTime}>
                                                Schedule
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                                <Button variant="destructive" onClick={handleDelete}>Delete</Button>
                            </>
                        )}
                        {campaign.status === 'scheduled' && (
                            <>
                                <Button onClick={handleSendScheduled}>
                                    <Send className="h-4 w-4 mr-2" />
                                    Send Now
                                </Button>
                                <Button variant="destructive" onClick={handleDelete}>Delete</Button>
                            </>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Total Recipients</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{campaign.total_recipients}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Sent</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-green-600">{campaign.sent_count}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Failed</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-red-600">{campaign.failed_count}</p>
                        </CardContent>
                    </Card>
                </div>

                {['draft', 'scheduled'].includes(campaign.status) && (
                    <SendingSettings campaign={campaign} />
                )}

                {previewHtml && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Email Preview</CardTitle>
                                    <CardDescription>Preview how this email will look to recipients</CardDescription>
                                </div>
                                <div className="flex items-center gap-1 rounded-md border p-1">
                                    <button
                                        onClick={() => setPreviewWidth('desktop')}
                                        className={`rounded px-2 py-1 text-sm transition-colors ${
                                            previewWidth === 'desktop'
                                                ? 'bg-accent text-accent-foreground'
                                                : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    >
                                        <Monitor className="h-4 w-4" />
                                    </button>
                                    <button
                                        onClick={() => setPreviewWidth('mobile')}
                                        className={`rounded px-2 py-1 text-sm transition-colors ${
                                            previewWidth === 'mobile'
                                                ? 'bg-accent text-accent-foreground'
                                                : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    >
                                        <Smartphone className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="flex justify-center">
                                <iframe
                                    srcDoc={previewHtml}
                                    className={`w-full rounded border bg-white ${
                                        previewWidth === 'mobile' ? 'max-w-[375px]' : ''
                                    }`}
                                    style={{ height: '600px' }}
                                    title="Email preview"
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {['draft', 'scheduled'].includes(campaign.status) && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Add Recipients</CardTitle>
                            <CardDescription>Add email recipients manually or import from CSV</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-2">
                                <form onSubmit={handleAddRecipient} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email Address</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={recipientEmail}
                                            onChange={(e) => setRecipientEmail(e.target.value)}
                                            placeholder="subscriber@example.com"
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Name (Optional)</Label>
                                        <Input
                                            id="name"
                                            value={recipientName}
                                            onChange={(e) => setRecipientName(e.target.value)}
                                            placeholder="John Doe"
                                        />
                                    </div>
                                    <Button type="submit">Add Recipient</Button>
                                </form>

                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label>Import from CSV</Label>
                                        <Input
                                            type="file"
                                            accept=".csv,.txt"
                                            onChange={(e) => setCsvFile(e.target.files?.[0] || null)}
                                        />
                                        <p className="text-sm text-muted-foreground">
                                            CSV should have "email" column. Optional "name" column.
                                        </p>
                                    </div>
                                    <Button
                                        variant="outline"
                                        onClick={handleImportCsv}
                                        disabled={!csvFile}
                                    >
                                        Import CSV
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <CardTitle>Recipients ({campaign.recipients?.length || 0})</CardTitle>
                        {selectedIds.length > 0 && (
                            <Button variant="outline" size="sm" onClick={() => setResendSelectedOpen(true)}>
                                <RotateCcw className="h-4 w-4 mr-2" />
                                Resend Selected ({selectedIds.length})
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-10">
                                        <Checkbox
                                            aria-label="Select all resendable recipients"
                                            checked={allResendableSelected}
                                            onCheckedChange={toggleSelectAll}
                                            disabled={resendableRecipients.length === 0}
                                        />
                                    </TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Attempts</TableHead>
                                    <TableHead>Sent At</TableHead>
                                    <TableHead>Error</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {campaign.recipients?.map((recipient) => {
                                    const isResendable = ['failed', 'sent'].includes(recipient.status);

                                    return (
                                        <TableRow key={recipient.id}>
                                            <TableCell>
                                                <Checkbox
                                                    aria-label={`Select ${recipient.email}`}
                                                    checked={selectedIds.includes(recipient.id)}
                                                    onCheckedChange={() => toggleRecipient(recipient.id)}
                                                    disabled={!isResendable}
                                                />
                                            </TableCell>
                                            <TableCell>{recipient.email}</TableCell>
                                            <TableCell>{recipient.name || '-'}</TableCell>
                                            <TableCell>
                                                <Badge className={recipientStatusColors[recipient.status]}>
                                                    {recipient.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {recipient.attempt_count > 0
                                                    ? `${recipient.success_count}/${recipient.attempt_count}`
                                                    : '-'}
                                            </TableCell>
                                            <TableCell>
                                                {recipient.sent_at
                                                    ? new Date(recipient.sent_at).toLocaleString()
                                                    : '-'}
                                            </TableCell>
                                            <TableCell className="max-w-[200px] truncate text-red-500 text-sm">
                                                {recipient.error_message || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {['draft', 'scheduled'].includes(campaign.status) && recipient.status === 'pending' && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleRemoveRecipient(recipient.id)}
                                                    >
                                                        Remove
                                                    </Button>
                                                )}
                                                {isResendable && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setResendRecipient(recipient)}
                                                    >
                                                        Resend
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                                {(!campaign.recipients || campaign.recipients.length === 0) && (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                                            No recipients yet. Add some above.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <ConfirmDialog
                open={sendConfirmKind !== null}
                onOpenChange={(open) => !open && setSendConfirmKind(null)}
                title={sendConfirmKind === 'scheduled' ? 'Send this scheduled campaign now?' : 'Send this campaign now?'}
                description={`This will send to ${
                    campaign.recipients?.filter((recipient) => recipient.status === 'pending').length ?? 0
                } pending recipient(s).`}
                confirmLabel="Send"
                onConfirm={confirmSend}
            />

            <ConfirmDialog
                open={resendSelectedOpen}
                onOpenChange={setResendSelectedOpen}
                title="Resend selected recipients?"
                description={`${selectedIds.length} recipient(s) will be queued for another send attempt.`}
                confirmLabel="Resend"
                onConfirm={confirmResendSelected}
            />

            <ConfirmDialog
                open={resendAllOpen}
                onOpenChange={setResendAllOpen}
                title="Resend all failed recipients?"
                description={`${failedRecipients.length} failed recipient(s) will be queued for another send attempt.`}
                confirmLabel="Resend"
                onConfirm={confirmResendAllFailed}
            />

            <ConfirmDialog
                open={resendCampaignOpen}
                onOpenChange={setResendCampaignOpen}
                title="Resend this campaign?"
                description={`${
                    campaign.recipients?.filter((recipient) => ['sent', 'failed'].includes(recipient.status)).length ?? 0
                } recipient(s) will be queued for another send.`}
                confirmLabel="Resend"
                onConfirm={confirmResendCampaign}
            />

            <ConfirmDialog
                open={resendRecipient !== null}
                onOpenChange={(open) => !open && setResendRecipient(null)}
                title="Resend this recipient?"
                description={resendRecipient ? `${resendRecipient.email} will be queued for another send attempt.` : undefined}
                confirmLabel="Resend"
                onConfirm={confirmResendRecipient}
            />

            <ResultDialog
                open={importResult !== null}
                onOpenChange={(open) => !open && setImportResult(null)}
                variant={(importResult?.added ?? 0) > 0 ? 'success' : 'error'}
                title={(importResult?.added ?? 0) > 0 ? 'Recipients Added' : 'No Recipients Added'}
                message={importResult ? buildImportMessage(importResult) : ''}
            />
        </>
    );
}

CampaignShow.layout = {
    breadcrumbs: [
        { title: 'Campaigns', href: '/campaigns' },
    ],
};
