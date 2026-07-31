import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/vitrine/page-header';

interface BlogPost {
    id: number;
    slug: string;
    title: string;
    content: string;
    published_at: string;
}

export default function BlogShow({ post }: { post: BlogPost }) {
    const lines = post.content.split('\n');
    const html = lines.map((line) => {
        if (line.startsWith('## ')) {
            return `<h2 class="mt-8 mb-4 text-2xl font-bold">${line.slice(3)}</h2>`;
        }
        if (line.startsWith('- ')) {
            return `<li class="ml-6 list-disc text-muted-foreground">${line.slice(2)}</li>`;
        }
        if (line.trim() === '') {
            return '<div class="h-4"></div>';
        }
        return `<p class="text-muted-foreground">${line}</p>`;
    }).join('\n');

    return (
        <>
            <Head title={`${post.title} - SenderBehi Blog`} />

            <section className="mx-auto max-w-3xl px-4 pt-24 pb-20 md:pt-32 md:pb-28">
                <Link href="/blog" className="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to blog
                </Link>
                <article className="mt-8">
                    <p className="text-sm text-muted-foreground">
                        {new Date(post.published_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                        })}
                    </p>
                    <h1 className="mt-2 text-4xl font-bold tracking-tight">{post.title}</h1>
                    <div
                        className="mt-8 prose prose-gray max-w-none dark:prose-invert"
                        dangerouslySetInnerHTML={{ __html: html }}
                    />
                </article>
            </section>
        </>
    );
}
