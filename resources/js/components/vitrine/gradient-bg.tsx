import { cn } from '@/lib/utils';

interface GradientBgProps {
    className?: string;
}

export function GradientBg({ className }: GradientBgProps) {
    return (
        <div className={cn('absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-primary/10 via-transparent to-transparent', className)} />
    );
}
