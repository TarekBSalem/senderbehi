import { cn } from '@/lib/utils';

interface Stat {
    value: string;
    label: string;
}

interface StatsSectionProps {
    stats: Stat[];
    className?: string;
}

export function StatsSection({ stats, className }: StatsSectionProps) {
    return (
        <section className={cn('border-t bg-muted/30 px-4 py-16', className)}>
            <div className="mx-auto max-w-6xl">
                <div className="grid gap-8 md:grid-cols-4">
                    {stats.map((stat) => (
                        <div key={stat.label} className="text-center">
                            <div className="text-3xl font-bold text-primary">{stat.value}</div>
                            <div className="mt-1 text-sm text-muted-foreground">{stat.label}</div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
