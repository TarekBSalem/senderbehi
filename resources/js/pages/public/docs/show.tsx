import { Head, Link } from '@inertiajs/react';

interface Doc {
    id: number;
    slug: string;
    title: string;
    content: string;
    category: string;
}

export default function DocShow({ doc }: { doc: Doc }) {
    const lines = doc.content.split('\n');
    const html = lines.map((line) => {
        if (line.startsWith('## ')) {
            return `<h2 class="mt-8 mb-4 text-2xl font-bold">${line.slice(3)}</h2>`;
        }
        if (line.startsWith('- ')) {
            return `<li class="ml-6 list-disc text-muted-foreground">${line.slice(2)}</li>`;
        }
        if (line.startsWith('1. ')) {
            return `<li class="ml-6 list-decimal text-muted-foreground">${line.slice(3)}</li>`;
        }
        if (line.trim() === '') {
            return '<div class="h-4"></div>';
        }
        return `<p class="text-muted-foreground">${line}</p>`;
    }).join('\n');

    return (
        <>
            <Head title={`${doc.title} - SenderBehi Docs`} />

            <section className="mx-auto max-w-3xl px-4 pt-24 pb-20 md:pt-32 md:pb-28">
                <Link href="/docs" className="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to documentation
                </Link>
                <article className="mt-8">
                    <div className="mb-2 text-sm font-medium text-primary">{doc.category}</div>
                    <h1 className="text-4xl font-bold tracking-tight">{doc.title}</h1>
                    <div
                        className="mt-8 prose prose-gray max-w-none dark:prose-invert"
                        dangerouslySetInnerHTML={{ __html: html }}
                    />
                </article>
            </section>
        </>
    );
}
