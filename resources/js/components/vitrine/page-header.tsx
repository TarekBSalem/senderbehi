import { cn } from '@/lib/utils';

interface PageHeaderProps {
    title: string;
    description?: string;
    className?: string;
}

export function PageHeader({ title, description, className }: PageHeaderProps) {
    return (
        <div className={cn('mx-auto max-w-3xl px-4 pt-24 pb-12 text-center md:pt-32 md:pb-16', className)}>
            <h1 className="text-4xl font-bold tracking-tight md:text-5xl">{title}</h1>
            {description && (
                <p className="mt-4 text-lg text-muted-foreground">{description}</p>
            )}
        </div>
    );
}
