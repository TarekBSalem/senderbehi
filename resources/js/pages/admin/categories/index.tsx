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
import { Input } from '@/components/ui/input';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Category {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    templates_count: number;
    created_at: string;
}

interface Props {
    categories: {
        data: Category[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
    };
}

export default function AdminCategoriesIndex({ categories, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const applySearch = () => {
        router.get('/admin/categories', { search: search || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDelete = (id: number, name: string, count: number) => {
        if (count > 0) {
            alert(`Cannot delete "${name}" — it has ${count} template(s). Reassign them first.`);
            return;
        }
        if (!confirm(`Delete category "${name}"?`)) return;
        router.delete(`/admin/categories/${id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Categories" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Categories</h1>
                        <p className="text-muted-foreground">Manage template categories</p>
                    </div>
                    <Link href="/admin/categories/create">
                        <Button>Create Category</Button>
                    </Link>
                </div>

                <div className="flex gap-4 items-center">
                    <Input
                        placeholder="Search categories..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') applySearch();
                        }}
                        className="max-w-xs"
                    />
                    {filters.search && (
                        <Button variant="ghost" onClick={() => {
                            setSearch('');
                            router.get('/admin/categories', {}, { preserveState: true });
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
                                    <TableHead>Status</TableHead>
                                    <TableHead>Templates</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {categories.data.map((cat) => (
                                    <TableRow key={cat.id}>
                                        <TableCell className="font-medium">{cat.name}</TableCell>
                                        <TableCell><code>{cat.slug}</code></TableCell>
                                        <TableCell>
                                            <Badge variant={cat.is_active ? 'default' : 'secondary'}>
                                                {cat.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{cat.templates_count}</TableCell>
                                        <TableCell className="text-muted-foreground text-sm">
                                            {new Date(cat.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex gap-1">
                                                <Link href={`/admin/categories/${cat.id}/edit`}>
                                                    <Button variant="ghost" size="sm">
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(cat.id, cat.name, cat.templates_count)}
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {categories.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                                            No categories found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {categories.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {categories.data.length} of {categories.total} categories
                        </p>
                        <div className="flex gap-2">
                            {categories.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get('/admin/categories', { ...filters, page: categories.current_page - 1 }, { preserveState: true })}
                                >
                                    Previous
                                </Button>
                            )}
                            {categories.current_page < categories.last_page && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get('/admin/categories', { ...filters, page: categories.current_page + 1 }, { preserveState: true })}
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

AdminCategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Categories', href: '/admin/categories' },
    ],
};
