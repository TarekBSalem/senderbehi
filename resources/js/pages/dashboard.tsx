import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface Stats {
    total_campaigns: number;
    emails_sent: number;
    total_recipients: number;
}

interface Campaign {
    id: number;
    name: string;
    status: string;
    sent_count: number;
    created_at: string;
    template: {
        name: string;
    };
}

interface Props {
    stats: Stats;
    recentCampaigns: Campaign[];
}

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    queued: 'bg-yellow-100 text-yellow-800',
    sending: 'bg-blue-100 text-blue-800',
    sent: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    partial: 'bg-orange-100 text-orange-800',
};

export default function Dashboard({ stats, recentCampaigns }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Dashboard</h1>
                        <p className="text-muted-foreground">Welcome back! Here's an overview of your campaigns.</p>
                    </div>
                    <Link href="/campaigns/create">
                        <Button>Create Campaign</Button>
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Campaigns</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_campaigns}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Emails Sent</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.emails_sent}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Recipients</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_recipients}</div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Recent Campaigns</CardTitle>
                                <CardDescription>Your latest email campaigns</CardDescription>
                            </div>
                            <Link href="/campaigns">
                                <Button variant="outline" size="sm">View All</Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {recentCampaigns.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground">
                                <p>No campaigns yet.</p>
                                <Link href="/campaigns/create">
                                    <Button variant="link">Create your first campaign</Button>
                                </Link>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {recentCampaigns.map((campaign) => (
                                    <div key={campaign.id} className="flex items-center justify-between border-b pb-4 last:border-0">
                                        <div>
                                            <Link
                                                href={`/campaigns/${campaign.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {campaign.name}
                                            </Link>
                                            <p className="text-sm text-muted-foreground">
                                                {campaign.template?.name} • {new Date(campaign.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm text-muted-foreground">
                                                {campaign.sent_count} sent
                                            </span>
                                            <Badge className={statusColors[campaign.status]}>
                                                {campaign.status}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Link href="/templates">
                                <Button variant="outline" className="w-full justify-start">
                                    Browse Templates
                                </Button>
                            </Link>
                            <Link href="/campaigns/create">
                                <Button variant="outline" className="w-full justify-start">
                                    Create Campaign
                                </Button>
                            </Link>
                            <Link href="/campaigns">
                                <Button variant="outline" className="w-full justify-start">
                                    View Campaigns
                                </Button>
                            </Link>
                            <Link href="/admin/smtp">
                                <Button variant="outline" className="w-full justify-start">
                                    SMTP Settings
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
    ],
};
