import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    LineChart,
    Line,
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
import { ArrowLeft, Download, Eye, MousePointerClick, MailOpen } from 'lucide-react';

interface CampaignInfo {
    id: number;
    name: string;
    status: string;
}

interface Stats {
    total_sent: number;
    total_failed: number;
    total_bounced: number;
    total_pending: number;
    unique_opens: number;
    unique_clicks: number;
    total_open_events: number;
    total_click_events: number;
    open_rate: number;
    click_rate: number;
    click_to_open_rate: number;
    bounce_rate: number;
}

interface TimelinePoint {
    date: string;
    opens: number;
    clicks: number;
}

interface DeviceBreakdown {
    name: string;
    value: number;
}

interface Recipient {
    id: number;
    email: string;
    name: string | null;
    status: string;
    sent_at: string | null;
    opened: boolean;
    clicked: boolean;
    open_count: number;
    click_count: number;
    bounced: boolean;
    first_opened_at: string | null;
    last_clicked_at: string | null;
    device: string;
    error_message: string | null;
}

interface Props {
    campaign: CampaignInfo;
    stats: Stats;
    timeline: TimelinePoint[];
    device_breakdown: DeviceBreakdown[];
    browser_breakdown: DeviceBreakdown[];
    recipients: Recipient[];
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

const chartColors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'];

const CHART_COLORS = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

function formatDate(dateStr: string | null) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function CampaignAnalytics({ campaign, stats, timeline, device_breakdown, browser_breakdown, recipients }: Props) {
    const handleExport = () => {
        router.get(`/campaigns/${campaign.id}/analytics/export`, {}, {
            onSuccess: (page) => {
                const data = page.props as { csv?: string; filename?: string };
                if (data.csv) {
                    const blob = new Blob([data.csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = data.filename ?? `campaign-${campaign.id}-analytics.csv`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                }
            },
        });
    };

    return (
        <>
            <Head title={`${campaign.name} - Analytics`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={`/campaigns/${campaign.id}`}
                            className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to campaign
                        </Link>
                        <div className="h-4 w-px bg-border" />
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-bold">{campaign.name}</h1>
                                <Badge className={statusColors[campaign.status]}>{campaign.status}</Badge>
                            </div>
                            <p className="text-sm text-muted-foreground">Campaign Analytics</p>
                        </div>
                    </div>
                    <Button variant="outline" onClick={handleExport}>
                        <Download className="mr-2 h-4 w-4" />
                        Export CSV
                    </Button>
                </div>

                {campaign.status === 'draft' && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            This campaign hasn't been sent yet. Analytics will be available after sending.
                        </CardContent>
                    </Card>
                )}

                {campaign.status !== 'draft' && (
                    <>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2 flex flex-row items-center justify-between space-y-0">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Sent</CardTitle>
                                    <MailOpen className="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <p className="text-3xl font-bold">{stats.total_sent.toLocaleString()}</p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {stats.total_failed} failed &bull; {stats.total_bounced} bounced
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2 flex flex-row items-center justify-between space-y-0">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Opens</CardTitle>
                                    <Eye className="h-4 w-4 text-blue-500" />
                                </CardHeader>
                                <CardContent>
                                    <p className="text-3xl font-bold text-blue-600">{stats.open_rate}%</p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {stats.unique_opens} unique &bull; {stats.total_open_events} total
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2 flex flex-row items-center justify-between space-y-0">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Clicks</CardTitle>
                                    <MousePointerClick className="h-4 w-4 text-green-500" />
                                </CardHeader>
                                <CardContent>
                                    <p className="text-3xl font-bold text-green-600">{stats.click_rate}%</p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {stats.unique_clicks} unique &bull; {stats.total_click_events} total
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2 flex flex-row items-center justify-between space-y-0">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">CTOR</CardTitle>
                                    <Eye className="h-4 w-4 text-purple-500" />
                                </CardHeader>
                                <CardContent>
                                    <p className="text-3xl font-bold text-purple-600">{stats.click_to_open_rate}%</p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Click-to-open rate &bull; {stats.bounce_rate}% bounced
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Opens & Clicks Over Time</CardTitle>
                                    <CardDescription>Daily tracking events since sent</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="h-72">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <LineChart data={timeline}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                                <XAxis
                                                    dataKey="date"
                                                    tick={{ fontSize: 11 }}
                                                    tickFormatter={(v) => v.slice(5)}
                                                    className="text-muted-foreground"
                                                />
                                                <YAxis className="text-muted-foreground" allowDecimals={false} />
                                                <Tooltip />
                                                <Line
                                                    type="monotone"
                                                    dataKey="opens"
                                                    stroke="#3b82f6"
                                                    strokeWidth={2}
                                                    dot={false}
                                                    name="Opens"
                                                />
                                                <Line
                                                    type="monotone"
                                                    dataKey="clicks"
                                                    stroke="#22c55e"
                                                    strokeWidth={2}
                                                    dot={false}
                                                    name="Clicks"
                                                />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="grid gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Device Breakdown</CardTitle>
                                        <CardDescription>Opens by device type</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="h-32">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <BarChart data={device_breakdown} layout="vertical">
                                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" horizontal={false} />
                                                    <XAxis type="number" className="text-muted-foreground" />
                                                    <YAxis type="category" dataKey="name" className="text-muted-foreground" width={70} />
                                                    <Tooltip />
                                                    <Bar dataKey="value" radius={[0, 4, 4, 0]}>
                                                        {device_breakdown.map((_, i) => (
                                                            <Cell key={i} fill={CHART_COLORS[i % CHART_COLORS.length]} />
                                                        ))}
                                                    </Bar>
                                                </BarChart>
                                            </ResponsiveContainer>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Browser Breakdown</CardTitle>
                                        <CardDescription>Opens by browser</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="h-32">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <BarChart data={browser_breakdown} layout="vertical">
                                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" horizontal={false} />
                                                    <XAxis type="number" className="text-muted-foreground" />
                                                    <YAxis type="category" dataKey="name" className="text-muted-foreground" width={90} />
                                                    <Tooltip />
                                                    <Bar dataKey="value" radius={[0, 4, 4, 0]}>
                                                        {browser_breakdown.map((_, i) => (
                                                            <Cell key={i} fill={CHART_COLORS[(i + 2) % CHART_COLORS.length]} />
                                                        ))}
                                                    </Bar>
                                                </BarChart>
                                            </ResponsiveContainer>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle>Recipients ({recipients.length})</CardTitle>
                                <CardDescription>Per-recipient tracking details</CardDescription>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Sent</TableHead>
                                            <TableHead>Opened</TableHead>
                                            <TableHead>Clicked</TableHead>
                                            <TableHead>First Open</TableHead>
                                            <TableHead>Device</TableHead>
                                            <TableHead>Error</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recipients.map((r) => (
                                            <TableRow key={r.id}>
                                                <TableCell className="font-medium">{r.email}</TableCell>
                                                <TableCell>{r.name || '-'}</TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={
                                                            r.bounced
                                                                ? 'bg-red-100 text-red-800'
                                                                : r.status === 'sent'
                                                                    ? 'bg-green-100 text-green-800'
                                                                    : r.status === 'failed'
                                                                        ? 'bg-red-100 text-red-800'
                                                                        : 'bg-gray-100 text-gray-800'
                                                        }
                                                    >
                                                        {r.bounced ? 'bounced' : r.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {formatDate(r.sent_at)}
                                                </TableCell>
                                                <TableCell>
                                                    {r.opened ? (
                                                        <span className="flex items-center gap-1 text-green-600">
                                                            <Eye className="h-3.5 w-3.5" />
                                                            {r.open_count}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {r.clicked ? (
                                                        <span className="flex items-center gap-1 text-blue-600">
                                                            <MousePointerClick className="h-3.5 w-3.5" />
                                                            {r.click_count}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {formatDate(r.first_opened_at)}
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {r.opened ? r.device : '-'}
                                                </TableCell>
                                                <TableCell className="max-w-[150px] truncate text-sm text-red-500">
                                                    {r.error_message || '-'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                        {recipients.length === 0 && (
                                            <TableRow>
                                                <TableCell colSpan={9} className="text-center py-8 text-muted-foreground">
                                                    No recipients in this campaign.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}
