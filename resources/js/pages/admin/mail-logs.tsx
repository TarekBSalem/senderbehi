import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Trash2 } from 'lucide-react';
import mailLogs from '@/routes/admin/mail-logs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ConfirmDialog from '@/components/confirm-dialog';
import { Input } from '@/components/ui/input';
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

interface MailLog {
    id: number;
    recipient_email: string | null;
    event: string;
    smtp_source: string | null;
    error_message: string | null;
    attempt: number | null;
    created_at: string;
    campaign: { id: number; name: string } | null;
    user: { id: number; name: string; email: string } | null;
}

interface Props {
    logs: {
        data: MailLog[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        event?: string;
    };
    stats: {
        failed_24h: number;
        failed_total: number;
        sent_24h: number;
        job_failed_total: number;
    };
}

const eventColors: Record<string, string> = {
    sent: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    bounced: 'bg-yellow-100 text-yellow-800',
    job_failed: 'bg-purple-100 text-purple-800',
};

const eventOptions = [
    { value: 'sent', label: 'Sent' },
    { value: 'failed', label: 'Failed' },
    { value: 'bounced', label: 'Bounced' },
    { value: 'job_failed', label: 'Job Failed' },
];

export default function AdminMailLogs({ logs, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [deleteTarget, setDeleteTarget] = useState<MailLog | null>(null);
    const [clearOpen, setClearOpen] = useState(false);

    const applyFilter = (key: string, value: string) => {
        router.get(mailLogs.index.url(), { ...filters, [key]: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const deleteLog = () => {
        if (!deleteTarget) {
            return;
        }

        router.delete(mailLogs.destroy.url(deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    };

    const clearAll = () => {
        setClearOpen(false);
        router.delete(mailLogs.clear.url());
    };

    return (
        <>
            <Head title="Mail Logs" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Mail Logs</h1>
                        <p className="text-muted-foreground">Send attempts and delivery failures for debugging</p>
                    </div>
                    <Button variant="destructive" onClick={() => setClearOpen(true)}>
                        Clear All
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Failed (24h)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-red-600">{stats.failed_24h}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Failed (all time)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-red-600">{stats.failed_total}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Sent (24h)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-green-600">{stats.sent_24h}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Job Failures</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-purple-600">{stats.job_failed_total}</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="flex gap-4 items-center">
                    <Input
                        placeholder="Search email, campaign or user..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') applyFilter('search', search);
                        }}
                        className="max-w-xs"
                    />
                    <Select
                        value={filters.event || ''}
                        onValueChange={(value) => applyFilter('event', value)}
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All events" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All events</SelectItem>
                            {eventOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {(filters.search || filters.event) && (
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                router.get(mailLogs.index.url(), {}, { preserveState: true });
                            }}
                        >
                            Clear
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Time</TableHead>
                                    <TableHead>Recipient</TableHead>
                                    <TableHead>Campaign</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Attempts</TableHead>
                                    <TableHead>SMTP</TableHead>
                                    <TableHead>Error</TableHead>
                                    <TableHead className="w-10"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {new Date(log.created_at).toLocaleString()}
                                        </TableCell>
                                        <TableCell>{log.recipient_email || '-'}</TableCell>
                                        <TableCell>{log.campaign?.name || '-'}</TableCell>
                                        <TableCell>{log.user?.email || '-'}</TableCell>
                                        <TableCell>
                                            <Badge className={eventColors[log.event] || 'bg-gray-100 text-gray-800'}>
                                                {log.event.replace('_', ' ')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{log.attempt ?? '-'}</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">{log.smtp_source || '-'}</Badge>
                                        </TableCell>
                                        <TableCell className="max-w-[260px] truncate text-sm" title={log.error_message || ''}>
                                            <span className={log.event === 'failed' || log.event === 'job_failed' ? 'text-red-500' : 'text-muted-foreground'}>
                                                {log.error_message || '-'}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Button variant="ghost" size="sm" onClick={() => setDeleteTarget(log)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {logs.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={9} className="text-center py-8 text-muted-foreground">
                                            No mail logs found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {logs.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {logs.data.length} of {logs.total} logs
                        </p>
                        <div className="flex gap-2">
                            {logs.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(mailLogs.index.url(), { ...filters, page: logs.current_page - 1 }, { preserveState: true })}
                                >
                                    Previous
                                </Button>
                            )}
                            {logs.current_page < logs.last_page && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(mailLogs.index.url(), { ...filters, page: logs.current_page + 1 }, { preserveState: true })}
                                >
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>

            <ConfirmDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleteTarget(null);
                    }
                }}
                title="Delete mail log?"
                description={`This will permanently delete the log entry for ${deleteTarget?.recipient_email ?? 'this recipient'}.`}
                confirmLabel="Delete"
                variant="destructive"
                onConfirm={deleteLog}
            />

            <ConfirmDialog
                open={clearOpen}
                onOpenChange={setClearOpen}
                title="Clear all mail logs?"
                description={`This will permanently delete all ${logs.total} log entries.`}
                confirmLabel="Clear All"
                variant="destructive"
                onConfirm={clearAll}
            />
        </>
    );
}

AdminMailLogs.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/mail-logs' },
        { title: 'Mail Logs', href: '/admin/mail-logs' },
    ],
};
