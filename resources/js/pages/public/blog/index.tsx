import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/vitrine/page-header';

interface BlogPost {
    id: number;
    slug: string;
    title: string;
    excerpt: string;
    published_at: string;
}

export default function BlogIndex({ posts }: { posts: { data: BlogPost[] } }) {
    return (
        <>
            <Head title="Blog - SenderBehi">
                <meta name="description" content="Email marketing tips, guides, and insights from the SenderBehi team." />
            </Head>

            <PageHeader
                title="Blog"
                description="Tips, guides, and insights for email marketing."
            />

            <section className="mx-auto max-w-4xl px-4 pb-20">
                <div className="grid gap-8">
                    {posts.data.length === 0 && (
                        <p className="text-center text-muted-foreground">No posts yet. Check back soon!</p>
                    )}
                    {posts.data.map((post) => (
                        <Link
                            key={post.id}
                            href={`/blog/${post.slug}`}
                            className="block rounded-lg border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-primary/50"
                        >
                            <h2 className="text-xl font-semibold">{post.title}</h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {new Date(post.published_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                })}
                            </p>
                            <p className="mt-3 text-muted-foreground">{post.excerpt}</p>
                        </Link>
                    ))}
                </div>
            </section>
        </>
    );
}
