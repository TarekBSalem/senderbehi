import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, ShieldBan, Trash2 } from 'lucide-react';
import suppressionRoutes from '@/routes/admin/suppressions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ConfirmDialog from '@/components/confirm-dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface Suppression {
    id: number;
    email: string;
    reason: string;
    created_at: string;
    campaign: { id: number; name: string } | null;
}

interface Props {
    suppressions: {
        data: Suppression[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        reason?: string;
    };
    stats: {
        total: number;
        bounce: number;
        complaint: number;
        manual: number;
    };
}

const reasonColors: Record<string, string> = {
    bounce: 'bg-red-100 text-red-800',
    complaint: 'bg-orange-100 text-orange-800',
    manual: 'bg-blue-100 text-blue-800',
};

const reasonOptions = [
    { value: 'bounce', label: 'Bounce' },
    { value: 'complaint', label: 'Complaint' },
    { value: 'manual', label: 'Manual' },
];

export default function AdminSuppressions({ suppressions, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [deleteTarget, setDeleteTarget] = useState<Suppression | null>(null);
    const [clearOpen, setClearOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        reason: 'manual',
    });

    const applyFilter = (key: string, value: string) => {
        router.get(suppressionRoutes.index.url(), { ...filters, [key]: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const addSuppression = () => {
        post(suppressionRoutes.store.url(), {
            onSuccess: () => reset(),
        });
    };

    const deleteSuppression = () => {
        if (!deleteTarget) {
            return;
        }

        router.delete(suppressionRoutes.destroy.url(deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    };

    const clearAll = () => {
        setClearOpen(false);
        router.delete(suppressionRoutes.clear.url());
    };

    return (
        <>
            <Head title="Suppression List" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Suppression List</h1>
                        <p className="text-muted-foreground">Emails blocked from receiving campaigns</p>
                    </div>
                    <Button variant="destructive" onClick={() => setClearOpen(true)}>
                        Clear All
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Total Suppressed</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.total}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Bounces</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-red-600">{stats.bounce}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Complaints</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-orange-600">{stats.complaint}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Manual</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-blue-600">{stats.manual}</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Add Email to Suppression List</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="flex flex-wrap items-end gap-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                addSuppression();
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="blocked@example.com"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="w-72"
                                />
                                {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="reason">Reason</Label>
                                <Select value={data.reason} onValueChange={(value) => setData('reason', value)}>
                                    <SelectTrigger className="w-44">
                                        <SelectValue placeholder="Select reason" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {reasonOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.reason && <p className="text-sm text-red-500">{errors.reason}</p>}
                            </div>
                            <Button type="submit" disabled={processing}>
                                <Plus className="h-4 w-4" />
                                Add
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <div className="flex gap-4 items-center">
                    <Input
                        placeholder="Search email..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') applyFilter('search', search);
                        }}
                        className="max-w-xs"
                    />
                    <Select value={filters.reason || ''} onValueChange={(value) => applyFilter('reason', value)}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All reasons" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All reasons</SelectItem>
                            {reasonOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {(filters.search || filters.reason) && (
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                router.get(suppressionRoutes.index.url(), {}, { preserveState: true });
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
                                    <TableHead>Email</TableHead>
                                    <TableHead>Reason</TableHead>
                                    <TableHead>Campaign</TableHead>
                                    <TableHead>Added</TableHead>
                                    <TableHead className="w-10"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {suppressions.data.map((suppression) => (
                                    <TableRow key={suppression.id}>
                                        <TableCell className="font-medium">{suppression.email}</TableCell>
                                        <TableCell>
                                            <Badge className={reasonColors[suppression.reason] || 'bg-gray-100 text-gray-800'}>
                                                {suppression.reason}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{suppression.campaign?.name || '-'}</TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {new Date(suppression.created_at).toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            <Button variant="ghost" size="sm" onClick={() => setDeleteTarget(suppression)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {suppressions.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                            <ShieldBan className="mx-auto mb-2 h-8 w-8" />
                                            No suppressed emails found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {suppressions.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {suppressions.data.length} of {suppressions.total} emails
                        </p>
                        <div className="flex gap-2">
                            {suppressions.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(suppressionRoutes.index.url(), { ...filters, page: suppressions.current_page - 1 }, { preserveState: true })}
                                >
                                    Previous
                                </Button>
                            )}
                            {suppressions.current_page < suppressions.last_page && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(suppressionRoutes.index.url(), { ...filters, page: suppressions.current_page + 1 }, { preserveState: true })}
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
                title="Remove from suppression list?"
                description={`${deleteTarget?.email ?? 'This email'} will be allowed to receive campaigns again.`}
                confirmLabel="Remove"
                variant="destructive"
                onConfirm={deleteSuppression}
            />

            <ConfirmDialog
                open={clearOpen}
                onOpenChange={setClearOpen}
                title="Clear suppression list?"
                description={`This will permanently remove all ${stats.total} suppressed emails. They will be allowed to receive campaigns again.`}
                confirmLabel="Clear All"
                variant="destructive"
                onConfirm={clearAll}
            />
        </>
    );
}

AdminSuppressions.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/suppressions' },
        { title: 'Suppression List', href: '/admin/suppressions' },
    ],
};
