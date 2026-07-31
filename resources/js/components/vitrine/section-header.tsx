import { cn } from '@/lib/utils';

interface SectionHeaderProps {
    title: string;
    subtitle?: string;
    centered?: boolean;
    className?: string;
}

export function SectionHeader({ title, subtitle, centered = true, className }: SectionHeaderProps) {
    return (
        <div className={cn(centered && 'text-center', className)}>
            <h2 className="text-3xl font-bold tracking-tight md:text-4xl">{title}</h2>
            {subtitle && (
                <p className="mt-4 text-lg text-muted-foreground">{subtitle}</p>
            )}
        </div>
    );
}
