import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    Legend,
} from 'recharts';

interface Stats {
    total_users: number;
    new_users_this_month: number;
    total_campaigns: number;
    campaigns_this_month: number;
    emails_sent_all_time: number;
    emails_sent_this_month: number;
    emails_failed_all_time: number;
    mrr_cents: number;
}

interface DailyVolume {
    date: string;
    sent: number;
}

interface StatusCount {
    status: string;
    count: number;
}

interface RecentCampaign {
    id: number;
    name: string;
    status: string;
    sent_count: number;
    total_recipients: number;
    user_name: string;
    created_at: string;
}

interface Plan {
    id: number;
    name: string;
    code: string;
    user_count: number;
}

interface Props {
    stats: Stats;
    daily_volume: DailyVolume[];
    campaigns_by_status: StatusCount[];
    recent_campaigns: RecentCampaign[];
    plans: Plan[];
}

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    queued: 'bg-yellow-100 text-yellow-800',
    sending: 'bg-blue-100 text-blue-800',
    sent: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    partial: 'bg-orange-100 text-orange-800',
};

const chartColors = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

export default function AdminAnalytics({
    stats,
    daily_volume,
    campaigns_by_status,
    recent_campaigns,
    plans,
}: Props) {
    const mrr = (stats.mrr_cents / 100).toLocaleString('en-US', {
        style: 'currency',
        currency: 'USD',
    });

    return (
        <>
            <Head title="Analytics" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-bold">Analytics</h1>
                    <p className="text-muted-foreground">Platform-wide metrics and trends</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Total Users</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.total_users.toLocaleString()}</p>
                            <p className="text-xs text-muted-foreground mt-1">
                                +{stats.new_users_this_month} this month
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Campaigns</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.total_campaigns.toLocaleString()}</p>
                            <p className="text-xs text-muted-foreground mt-1">
                                +{stats.campaigns_this_month} this month
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Emails Sent</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.emails_sent_all_time.toLocaleString()}</p>
                            <p className="text-xs text-muted-foreground mt-1">
                                {stats.emails_sent_this_month.toLocaleString()} this month
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">MRR</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{mrr}</p>
                            <p className="text-xs text-muted-foreground mt-1">
                                {stats.emails_failed_all_time.toLocaleString()} failed emails
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Daily Email Volume</CardTitle>
                            <CardDescription>Last 30 days</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={daily_volume}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis
                                            dataKey="date"
                                            tick={{ fontSize: 11 }}
                                            tickFormatter={(v) => v.slice(5)}
                                            className="text-muted-foreground"
                                        />
                                        <YAxis className="text-muted-foreground" />
                                        <Tooltip />
                                        <Bar dataKey="sent" fill="#3b82f6" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Campaigns by Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={campaigns_by_status}
                                            dataKey="count"
                                            nameKey="status"
                                            cx="50%"
                                            cy="50%"
                                            outerRadius={80}
                                            label={false}
                                        >
                                            {campaigns_by_status.map((_, i) => (
                                                <Cell key={i} fill={chartColors[i % chartColors.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Plan Distribution</CardTitle>
                            <CardDescription>Users by subscription plan</CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Users</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {plans.map((plan) => (
                                        <TableRow key={plan.id}>
                                            <TableCell className="font-medium">{plan.name}</TableCell>
                                            <TableCell>{plan.user_count}</TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow>
                                        <TableCell className="font-medium text-muted-foreground">No plan</TableCell>
                                        <TableCell>
                                            {stats.total_users - plans.reduce((s, p) => s + p.user_count, 0)}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Campaigns</CardTitle>
                            <CardDescription>Latest campaigns across all users</CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Campaign</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Sent</TableHead>
                                        <TableHead>Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recent_campaigns.map((c) => (
                                        <TableRow key={c.id}>
                                            <TableCell className="font-medium">{c.name}</TableCell>
                                            <TableCell>{c.user_name}</TableCell>
                                            <TableCell>
                                                <Badge className={statusColors[c.status]}>{c.status}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                {c.sent_count}/{c.total_recipients}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground text-sm">
                                                {new Date(c.created_at).toLocaleDateString()}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {recent_campaigns.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                                                No campaigns yet.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminAnalytics.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/analytics' },
        { title: 'Analytics', href: '/admin/analytics' },
    ],
};
