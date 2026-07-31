import { Head, Link, router } from '@inertiajs/react';
import adminUsers from '@/routes/admin/users';
import campaignRoutes from '@/routes/campaigns';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

import { useState } from 'react';

interface Plan {
    id: number;
    name: string;
    code: string;
    email_limit: number;
}

interface UserDetail {
    id: number;
    name: string;
    email: string;
    roles: string[];
    email_verified_at: string | null;
    blocked_at: string | null;
    created_at: string;
    campaigns_count: number;
    emails_sent: number;
    plan: Plan | null;
    monthly_email_count: number;
    quota_remaining: number;
}

interface Campaign {
    id: number;
    name: string;
    status: string;
    template: string | null;
    sent_count: number;
    total_recipients: number;
    created_at: string;
}

interface Props {
    user: UserDetail;
    campaigns: Campaign[];
    roles: string[];
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

export default function AdminUsersShow({ user, campaigns, roles, plans }: Props) {
    const [selectedRole, setSelectedRole] = useState(user.roles[0] || '');
    const [selectedPlanId, setSelectedPlanId] = useState(String(user.plan?.id ?? ''));
    const [updating, setUpdating] = useState(false);

    const updateRole = () => {
        if (updating || selectedRole === (user.roles[0] || '')) return;
        setUpdating(true);
        router.put(adminUsers.role.url({ user: user.id }), { role: selectedRole }, {
            preserveScroll: true,
            onFinish: () => setUpdating(false),
        });
    };

    const updatePlan = () => {
        if (updating) return;
        setUpdating(true);
        router.put(adminUsers.plan.url({ user: user.id }), {
            plan_id: selectedPlanId ? Number(selectedPlanId) : null,
        }, {
            preserveScroll: true,
            onFinish: () => setUpdating(false),
        });
    };

    const toggleBlock = () => {
        if (!confirm(user.blocked_at ? 'Unsuspend this user?' : 'Suspend this user?')) return;
        const url = user.blocked_at
            ? adminUsers.unblock.url({ user: user.id })
            : adminUsers.block.url({ user: user.id });
        router.post(url, {}, { preserveScroll: true });
    };

    const usagePercent = user.plan
        ? Math.min(100, Math.round((user.monthly_email_count / user.plan.email_limit) * 100))
        : 0;

    return (
        <>
            <Head title={user.name} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div>
                    <Link href={adminUsers.index.url()} className="text-sm text-muted-foreground hover:underline">
                        &larr; Back to users
                    </Link>
                    <h1 className="text-2xl font-bold mt-2">{user.name}</h1>
                    <p className="text-muted-foreground">{user.email}</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Campaigns</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{user.campaigns_count}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Emails Sent</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{user.emails_sent}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Plan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xl font-bold">{user.plan?.name ?? 'None'}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Badge variant={user.blocked_at ? 'destructive' : 'default'} className="text-base">
                                {user.blocked_at ? 'Suspended' : 'Active'}
                            </Badge>
                        </CardContent>
                    </Card>
                </div>

                {user.plan && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Monthly Usage ({user.plan.name})</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex justify-between text-sm">
                                <span>{user.monthly_email_count.toLocaleString()} / {user.plan.email_limit.toLocaleString()} emails</span>
                                <span className={usagePercent >= 100 ? 'text-destructive font-bold' : 'text-muted-foreground'}>
                                    {usagePercent}%
                                </span>
                            </div>
                            <div className="h-2 w-full rounded-full bg-muted">
                                <div
                                    className="h-2 rounded-full bg-primary transition-all"
                                    style={{ width: `${usagePercent}%` }}
                                />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {user.quota_remaining.toLocaleString()} emails remaining this month
                            </p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>User Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-muted-foreground">Name</p>
                                <p className="font-medium">{user.name}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Email</p>
                                <p className="font-medium">{user.email}</p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Email Verified</p>
                                <p className="font-medium">
                                    {user.email_verified_at
                                        ? new Date(user.email_verified_at).toLocaleDateString()
                                        : 'Not verified'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Joined</p>
                                <p className="font-medium">{new Date(user.created_at).toLocaleDateString()}</p>
                            </div>
                        </div>

                        <div className="flex items-end gap-4 pt-2 border-t">
                            <div>
                                <p className="text-sm text-muted-foreground mb-2">Role</p>
                                <Select value={selectedRole} onValueChange={setSelectedRole}>
                                    <SelectTrigger className="w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role} value={role}>{role}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button
                                onClick={updateRole}
                                disabled={updating || selectedRole === (user.roles[0] || '')}
                            >
                                Update Role
                            </Button>
                        </div>

                        <div className="flex items-end gap-4 pt-2 border-t">
                            <div>
                                <p className="text-sm text-muted-foreground mb-2">Subscription Plan</p>
                                <Select value={selectedPlanId} onValueChange={setSelectedPlanId}>
                                    <SelectTrigger className="w-48">
                                        <SelectValue placeholder="No plan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">No plan</SelectItem>
                                        {plans.map((plan) => (
                                            <SelectItem key={plan.id} value={String(plan.id)}>
                                                {plan.name} ({plan.email_limit.toLocaleString()}/mo)
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button onClick={updatePlan} disabled={updating}>
                                Update Plan
                            </Button>
                            <Button
                                variant={user.blocked_at ? 'outline' : 'destructive'}
                                onClick={toggleBlock}
                                className="ml-auto"
                            >
                                {user.blocked_at ? 'Unsuspend User' : 'Suspend User'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Campaigns</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Template</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Recipients</TableHead>
                                    <TableHead>Sent</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {campaigns.map((c) => (
                                    <TableRow key={c.id}>
                                        <TableCell className="font-medium">{c.name}</TableCell>
                                        <TableCell>{c.template}</TableCell>
                                        <TableCell>
                                            <Badge className={statusColors[c.status]}>{c.status}</Badge>
                                        </TableCell>
                                        <TableCell>{c.total_recipients}</TableCell>
                                        <TableCell>{c.sent_count}</TableCell>
                                        <TableCell>{new Date(c.created_at).toLocaleDateString()}</TableCell>
                                        <TableCell>
                                            <Link href={campaignRoutes.show.url({ campaign: c.id })}>
                                                <Button variant="ghost" size="sm">View</Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {campaigns.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                                            No campaigns yet.
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

AdminUsersShow.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Users', href: '/admin/users' },
        { title: 'User Detail', href: '' },
    ],
};
