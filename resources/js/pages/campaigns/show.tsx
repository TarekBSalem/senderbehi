import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useState } from 'react';

interface Recipient {
    id: number;
    email: string;
    name: string | null;
    status: string;
    sent_at: string | null;
    error_message: string | null;
}

interface Campaign {
    id: number;
    name: string;
    subject: string;
    status: string;
    sent_count: number;
    failed_count: number;
    total_recipients: number;
    created_at: string;
    template: {
        name: string;
        slug: string;
    };
    recipients: Recipient[];
}

interface Props {
    campaign: Campaign;
}

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
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

export default function CampaignShow({ campaign }: Props) {
    const [recipientEmail, setRecipientEmail] = useState('');
    const [recipientName, setRecipientName] = useState('');
    const [csvFile, setCsvFile] = useState<File | null>(null);

    const handleAddRecipient = (e: React.FormEvent) => {
        e.preventDefault();
        if (!recipientEmail) return;

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
        if (!csvFile) return;

        const formData = new FormData();
        formData.append('file', csvFile);

        router.post(`/campaigns/${campaign.id}/recipients/import`, formData, {
            onSuccess: () => setCsvFile(null),
        });
    };

    const handleSendCampaign = () => {
        if (confirm('Are you sure you want to send this campaign?')) {
            router.post(`/campaigns/${campaign.id}/send`);
        }
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
                    </div>
                    <div className="flex gap-2">
                        {campaign.status === 'draft' && (
                            <>
                                <Button onClick={handleSendCampaign}>Send Campaign</Button>
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

                {campaign.status === 'draft' && (
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
                    <CardHeader>
                        <CardTitle>Recipients ({campaign.recipients?.length || 0})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Sent At</TableHead>
                                    <TableHead>Error</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {campaign.recipients?.map((recipient) => (
                                    <TableRow key={recipient.id}>
                                        <TableCell>{recipient.email}</TableCell>
                                        <TableCell>{recipient.name || '-'}</TableCell>
                                        <TableCell>
                                            <Badge className={recipientStatusColors[recipient.status]}>
                                                {recipient.status}
                                            </Badge>
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
                                            {campaign.status === 'draft' && recipient.status === 'pending' && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleRemoveRecipient(recipient.id)}
                                                >
                                                    Remove
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {(!campaign.recipients || campaign.recipients.length === 0) && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                                            No recipients yet. Add some above.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CampaignShow.layout = {
    breadcrumbs: [
        { title: 'Campaigns', href: '/campaigns' },
    ],
};
