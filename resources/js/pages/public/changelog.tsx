import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/vitrine/page-header';

interface ChangelogEntry {
    id: number;
    version: string;
    title: string;
    content: string;
    released_at: string;
}

export default function Changelog({ entries }: { entries: ChangelogEntry[] }) {
    return (
        <>
            <Head title="Changelog - SenderBehi">
                <meta name="description" content="Stay up to date with the latest SenderBehi releases and updates." />
            </Head>

            <PageHeader
                title="Changelog"
                description="What's new in SenderBehi."
            />

            <section className="mx-auto max-w-3xl px-4 pb-20">
                <div className="relative">
                    <div className="absolute left-4 top-0 bottom-0 w-px bg-border" />

                    <div className="space-y-12">
                        {entries.length === 0 && (
                            <p className="pt-12 text-center text-muted-foreground">No releases yet. Check back soon!</p>
                        )}
                        {entries.map((entry) => {
                            const items = entry.content
                                .split('\n')
                                .filter((l) => l.startsWith('- '))
                                .map((l) => l.slice(2));

                            return (
                                <div key={entry.id} className="relative pl-12">
                                    <div className="absolute left-2.5 top-1.5 size-3 rounded-full border-2 border-primary bg-background" />

                                    <div className="mb-1 flex items-center gap-3">
                                        <span className="rounded-md bg-primary/10 px-2.5 py-0.5 text-sm font-semibold text-primary">
                                            v{entry.version}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {new Date(entry.released_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            })}
                                        </span>
                                    </div>

                                    <h3 className="mt-2 text-xl font-bold">{entry.title}</h3>

                                    <ul className="mt-3 space-y-2">
                                        {items.map((item, i) => (
                                            <li key={i} className="text-muted-foreground">{item}</li>
                                        ))}
                                    </ul>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>
        </>
    );
}
