import { cn } from '@/lib/utils';

interface FeatureCardProps {
    title: string;
    description: string;
    icon?: React.ReactNode;
    className?: string;
}

export function FeatureCard({ title, description, icon, className }: FeatureCardProps) {
    return (
        <div className={cn('rounded-lg border bg-card p-6 shadow-sm transition-shadow hover:shadow-md', className)}>
            {icon && <div className="mb-4">{icon}</div>}
            <h3 className="text-lg font-semibold">{title}</h3>
            <p className="mt-2 text-sm text-muted-foreground">{description}</p>
        </div>
    );
}
