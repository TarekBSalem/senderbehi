import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

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
    };
}

interface Props {
    campaigns: {
        data: Campaign[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    queued: 'bg-yellow-100 text-yellow-800',
    sending: 'bg-blue-100 text-blue-800',
    sent: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    partial: 'bg-orange-100 text-orange-800',
};

export default function CampaignIndex({ campaigns }: Props) {
    return (
        <>
            <Head title="Campaigns" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Campaigns</h1>
                        <p className="text-muted-foreground">Manage your email campaigns</p>
                    </div>
                    <Link href="/campaigns/create">
                        <Button>Create Campaign</Button>
                    </Link>
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Template</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Recipients</TableHead>
                                    <TableHead>Sent</TableHead>
                                    <TableHead>Failed</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {campaigns.data.map((campaign) => (
                                    <TableRow key={campaign.id}>
                                        <TableCell className="font-medium">{campaign.name}</TableCell>
                                        <TableCell>{campaign.template?.name}</TableCell>
                                        <TableCell>
                                            <Badge className={statusColors[campaign.status]}>
                                                {campaign.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{campaign.total_recipients}</TableCell>
                                        <TableCell>{campaign.sent_count}</TableCell>
                                        <TableCell>{campaign.failed_count}</TableCell>
                                        <TableCell>
                                            {new Date(campaign.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            <Link href={`/campaigns/${campaign.id}`}>
                                                <Button variant="ghost" size="sm">View</Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {campaigns.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                                            No campaigns yet. Create your first campaign to get started.
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

CampaignIndex.layout = {
    breadcrumbs: [
        { title: 'Campaigns', href: '/campaigns' },
    ],
};
