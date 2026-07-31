import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface CTASectionProps {
    title: string;
    description: string;
    cta: { label: string; href: string };
    variant?: 'primary' | 'muted';
    className?: string;
}

export function CTASection({ title, description, cta, variant = 'primary', className }: CTASectionProps) {
    return (
        <section className={cn(
            'px-4 py-20',
            variant === 'primary' && 'bg-primary text-primary-foreground',
            variant === 'muted' && 'border-t bg-muted/30',
            className,
        )}>
            <div className="mx-auto max-w-4xl text-center">
                <h2 className="text-3xl font-bold tracking-tight">{title}</h2>
                <p className={cn(
                    'mt-4 text-lg',
                    variant === 'primary' && 'text-primary-foreground/80',
                    variant === 'muted' && 'text-muted-foreground',
                )}>
                    {description}
                </p>
                <div className="mt-8">
                    <Link href={cta.href}>
                        <Button
                            size="lg"
                            variant={variant === 'primary' ? 'secondary' : 'default'}
                        >
                            {cta.label}
                        </Button>
                    </Link>
                </div>
            </div>
        </section>
    );
}
