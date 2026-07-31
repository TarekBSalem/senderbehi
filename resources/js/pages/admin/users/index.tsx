import { Head, Link, router } from '@inertiajs/react';
import adminUsers from '@/routes/admin/users';
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
import { useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
    email_verified_at: string | null;
    blocked_at: string | null;
    campaigns_count: number;
    created_at: string;
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        role?: string;
        status?: string;
    };
    roles: string[];
}

export default function AdminUsersIndex({ users, filters, roles }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const applyFilter = (key: string, value: string) => {
        router.get(adminUsers.index.url(), { ...filters, [key]: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Users</h1>
                        <p className="text-muted-foreground">Manage platform users</p>
                    </div>
                    <Link href={adminUsers.create.url()}>
                        <Button>Create User</Button>
                    </Link>
                </div>

                <div className="flex gap-4 items-center">
                    <Input
                        placeholder="Search by name or email..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') applyFilter('search', search);
                        }}
                        className="max-w-xs"
                    />
                    <Select
                        value={filters.role || ''}
                        onValueChange={(v) => applyFilter('role', v)}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All roles" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All roles</SelectItem>
                            {roles.map((role) => (
                                <SelectItem key={role} value={role}>{role}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.status || ''}
                        onValueChange={(v) => applyFilter('status', v)}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="blocked">Suspended</SelectItem>
                        </SelectContent>
                    </Select>
                    {Object.keys(filters).some(k => filters[k as keyof typeof filters]) && (
                        <Button variant="ghost" onClick={() => {
                            setSearch('');
                            router.get(adminUsers.index.url(), {}, { preserveState: true });
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
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Verified</TableHead>
                                    <TableHead>Campaigns</TableHead>
                                    <TableHead>Joined</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="font-medium">{user.name}</TableCell>
                                        <TableCell>{user.email}</TableCell>
                                        <TableCell>
                                            {user.roles.map((role) => (
                                                <Badge key={role} variant="secondary" className="mr-1">
                                                    {role}
                                                </Badge>
                                            ))}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={user.blocked_at ? 'destructive' : 'default'}>
                                                {user.blocked_at ? 'Suspended' : 'Active'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {user.email_verified_at
                                                ? new Date(user.email_verified_at).toLocaleDateString()
                                                : <span className="text-muted-foreground">No</span>
                                            }
                                        </TableCell>
                                        <TableCell>{user.campaigns_count}</TableCell>
                                        <TableCell>
                                            {new Date(user.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            <Link href={adminUsers.show.url(user.id)}>
                                                <Button variant="ghost" size="sm">View</Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {users.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                                            No users found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {users.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {users.data.length} of {users.total} users
                        </p>
                        <div className="flex gap-2">
                            {users.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(adminUsers.index.url(), { ...filters, page: users.current_page - 1 }, { preserveState: true })}
                                >
                                    Previous
                                </Button>
                            )}
                            {users.current_page < users.last_page && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(adminUsers.index.url(), { ...filters, page: users.current_page + 1 }, { preserveState: true })}
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

AdminUsersIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Users', href: '/admin/users' },
    ],
};
