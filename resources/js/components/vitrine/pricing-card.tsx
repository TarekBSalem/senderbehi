import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PricingCardProps {
    name: string;
    price: string;
    period?: string;
    description: string;
    features: Record<string, boolean | number | string>;
    cta: { label: string; href: string };
    highlighted?: boolean;
    isFree?: boolean;
    className?: string;
}

export function PricingCard({ name, price, period = '/month', description, features, cta, highlighted, isFree, className }: PricingCardProps) {
    return (
        <div className={cn(
            'flex flex-col rounded-lg border bg-card p-6 shadow-sm transition-shadow hover:shadow-md',
            highlighted && 'border-primary ring-1 ring-primary',
            className,
        )}>
            {highlighted && (
                <span className="mb-4 inline-flex self-start rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                    Most Popular
                </span>
            )}
            <h3 className="text-xl font-bold">{name}</h3>
            <p className="mt-1 text-sm text-muted-foreground">{description}</p>
            <div className="mt-4">
                <span className="text-4xl font-bold">{price}</span>
                {!isFree && <span className="text-muted-foreground">{period}</span>}
            </div>
            <ul className="mt-8 space-y-3">
                {Object.entries(features).map(([key, value]) => (
                    <li key={key} className="flex items-start gap-2 text-sm">
                        <Check className="mt-0.5 size-4 shrink-0 text-primary" />
                        <span>
                            {value === false
                                ? `No ${key.replace(/_/g, ' ')}`
                                : typeof value === 'number'
                                    ? `Up to ${value.toLocaleString()} ${key.replace(/_/g, ' ')}`
                                    : typeof value === 'string'
                                        ? value
                                        : key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                        </span>
                    </li>
                ))}
            </ul>
            <div className="mt-auto pt-8">
                <Link href={cta.href}>
                    <Button className="w-full" variant={highlighted ? 'default' : 'outline'}>
                        {cta.label}
                    </Button>
                </Link>
            </div>
        </div>
    );
}
