import { SectionHeader } from '@/components/vitrine/section-header';
import { FeatureCard } from '@/components/vitrine/feature-card';
import { cn } from '@/lib/utils';

interface Feature {
    title: string;
    description: string;
    icon?: React.ReactNode;
}

interface FeaturesSectionProps {
    title: string;
    subtitle?: string;
    features: Feature[];
    columns?: 2 | 3 | 4;
    className?: string;
    variant?: 'default' | 'muted';
}

export function FeaturesSection({ title, subtitle, features, columns = 3, className, variant = 'default' }: FeaturesSectionProps) {
    return (
        <section className={cn('px-4 py-20', variant === 'muted' && 'border-t bg-muted/30', className)}>
            <div className="mx-auto max-w-7xl">
                <SectionHeader title={title} subtitle={subtitle} />
                <div className={cn(
                    'mt-16 grid gap-8',
                    columns === 2 && 'md:grid-cols-2',
                    columns === 3 && 'md:grid-cols-3',
                    columns === 4 && 'md:grid-cols-4',
                )}>
                    {features.map((feature) => (
                        <FeatureCard key={feature.title} {...feature} />
                    ))}
                </div>
            </div>
        </section>
    );
}
