import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/vitrine/page-header';
import { BookOpen } from 'lucide-react';

interface Doc {
    id: number;
    slug: string;
    title: string;
    content: string;
    category: string;
    sort_order: number;
}

export default function DocsIndex({ categories }: { categories: Record<string, Doc[]> }) {
    const categoryEntries = Object.entries(categories);

    return (
        <>
            <Head title="Documentation - SenderBehi">
                <meta name="description" content="Comprehensive documentation for SenderBehi email marketing platform. Learn how to create campaigns, manage templates, and analyze results." />
            </Head>

            <PageHeader
                title="Documentation"
                description="Everything you need to know about using SenderBehi."
            />

            <section className="mx-auto max-w-4xl px-4 pb-20">
                {categoryEntries.length === 0 && (
                    <p className="text-center text-muted-foreground">No documentation yet. Check back soon!</p>
                )}
                <div className="space-y-12">
                    {categoryEntries.map(([category, docs]) => (
                        <div key={category}>
                            <h2 className="mb-6 text-2xl font-bold">{category}</h2>
                            <div className="grid gap-4 md:grid-cols-2">
                                {docs.map((doc) => (
                                    <Link
                                        key={doc.id}
                                        href={`/docs/${doc.slug}`}
                                        className="group rounded-lg border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-primary/50"
                                    >
                                        <div className="mb-3 flex items-center gap-2">
                                            <BookOpen className="size-4 text-primary" />
                                            <h3 className="font-semibold">{doc.title}</h3>
                                        </div>
                                        <p className="text-sm text-muted-foreground line-clamp-2">
                                            {doc.content.replace(/^##.*$/m, '').replace(/#/g, '').trim().slice(0, 150)}...
                                        </p>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </section>
        </>
    );
}
