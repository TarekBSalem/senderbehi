import { Head, Link, router } from '@inertiajs/react';
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

interface Template {
    id: number;
    name: string;
    slug: string;
    subject: string;
    category_id: number | null;
    category_name: string | null;
    is_active: boolean;
    created_at: string;
}

interface Category {
    id: number;
    name: string;
}

interface Props {
    templates: {
        data: Template[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    categories: Category[];
    filters: {
        search?: string;
        category?: string;
        is_active?: string;
    };
}

export default function AdminTemplatesIndex({ templates, categories, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const applyFilter = (key: string, value: string) => {
        router.get('/admin/templates', { ...filters, [key]: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDelete = (id: number, name: string) => {
        if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
        router.delete(`/admin/templates/${id}`, {
            preserveScroll: true,
        });
    };

    const hasFilters = Object.keys(filters).some(k => filters[k as keyof typeof filters]);

    return (
        <>
            <Head title="Templates" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Templates</h1>
                        <p className="text-muted-foreground">Manage system email templates</p>
                    </div>
                    <Link href="/admin/templates/create">
                        <Button>Create Template</Button>
                    </Link>
                </div>

                <div className="flex gap-4 items-center">
                    <Input
                        placeholder="Search by name or category..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') applyFilter('search', search);
                        }}
                        className="max-w-xs"
                    />
                    <Select
                        value={filters.category || ''}
                        onValueChange={(v) => applyFilter('category', v)}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All categories</SelectItem>
                            {categories.map((cat) => (
                                <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
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
                    {hasFilters && (
                        <Button variant="ghost" onClick={() => {
                            setSearch('');
                            router.get('/admin/templates', {}, { preserveState: true });
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
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {templates.data.map((t) => (
                                    <TableRow key={t.id}>
                                        <TableCell className="font-medium">{t.name}</TableCell>
                                        <TableCell><code>{t.slug}</code></TableCell>
                                        <TableCell className="max-w-xs truncate">{t.subject}</TableCell>
                                        <TableCell>{t.category_name}</TableCell>
                                        <TableCell>
                                            <Badge variant={t.is_active ? 'default' : 'secondary'}>
                                                {t.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm">
                                            {new Date(t.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex gap-1">
                                                <Link href={`/admin/templates/${t.id}/edit`}>
                                                    <Button variant="ghost" size="sm">
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(t.id, t.name)}
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {templates.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                                            No templates found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {templates.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {templates.data.length} of {templates.total} templates
                        </p>
                        <div className="flex gap-2">
                            {templates.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get('/admin/templates', { ...filters, page: templates.current_page - 1 }, { preserveState: true })}
                                >
                                    Previous
                                </Button>
                            )}
                            {templates.current_page < templates.last_page && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get('/admin/templates', { ...filters, page: templates.current_page + 1 }, { preserveState: true })}
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

AdminTemplatesIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Templates', href: '/admin/templates' },
    ],
};
