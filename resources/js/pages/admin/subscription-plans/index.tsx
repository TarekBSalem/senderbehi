import { Head, Link, router } from '@inertiajs/react';
import subscriptionPlans from '@/routes/admin/subscription-plans';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Plan {
    id: number;
    name: string;
    code: string;
    price_cents: number;
    email_limit: number;
    features: Record<string, boolean | number> | null;
    sort_order: number;
    is_active: boolean;
    created_at: string;
}

interface Props {
    plans: {
        data: Plan[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        is_active?: string;
    };
}

function formatPrice(cents: number): string {
    return '$' + (cents / 100).toFixed(2);
}

function formatEmailLimit(limit: number): string {
    if (limit >= 1000) {
        return (limit / 1000).toFixed(0) + ',000';
    }
    return String(limit);
}

export default function AdminSubscriptionPlansIndex({ plans, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const applyFilter = (key: string, value: string) => {
        router.get(subscriptionPlans.index.url(), { ...filters, [key]: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDelete = (id: number, name: string) => {
        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
        router.delete(subscriptionPlans.destroy.url({ plan: id }), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Subscription Plans" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Subscription Plans</h1>
                        <p className="text-muted-foreground">Manage pricing tiers and features</p>
                    </div>
                    <Link href={subscriptionPlans.create.url()}>
                        <Button>Create Plan</Button>
                    </Link>
                </div>

                <div className="flex gap-4 items-center">
                    <Input
                        placeholder="Search by name or code..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') applyFilter('search', search);
                        }}
                        className="max-w-xs"
                    />
                    <Select
                        value={filters.is_active || ''}
                        onValueChange={(v) => applyFilter('is_active', v)}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All status</SelectItem>
                            <SelectItem value="1">Active</SelectItem>
                            <SelectItem value="0">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                    {Object.keys(filters).some(k => filters[k as keyof typeof filters]) && (
                        <Button variant="ghost" onClick={() => {
                            setSearch('');
                            router.get(subscriptionPlans.index.url(), {}, { preserveState: true });
                        }}>
                            Clear
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Email Limit</TableHead>
                                    <TableHead>Order</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {plans.data.map((plan) => (
                                    <TableRow key={plan.id}>
                                        <TableCell className="font-medium">{plan.name}</TableCell>
                                        <TableCell><code>{plan.code}</code></TableCell>
                                        <TableCell>{formatPrice(plan.price_cents)}</TableCell>
                                        <TableCell>{formatEmailLimit(plan.email_limit)}</TableCell>
                                        <TableCell>{plan.sort_order}</TableCell>
                                        <TableCell>
                                            <Badge variant={plan.is_active ? 'default' : 'secondary'}>
                                                {plan.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex gap-1">
                                                <Link href={subscriptionPlans.edit.url({ plan: plan.id })}>
                                                    <Button variant="ghost" size="sm">
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(plan.id, plan.name)}
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {plans.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                                            No subscription plans found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {plans.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {plans.data.length} of {plans.total} plans
                        </p>
                        <div className="flex gap-2">
                            {plans.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(subscriptionPlans.index.url(), { ...filters, page: plans.current_page - 1 }, { preserveState: true })}
                                >
                                    Previous
                                </Button>
                            )}
                            {plans.current_page < plans.last_page && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(subscriptionPlans.index.url(), { ...filters, page: plans.current_page + 1 }, { preserveState: true })}
                                >
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

AdminSubscriptionPlansIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Plans', href: '/admin/subscription-plans' },
    ],
};
