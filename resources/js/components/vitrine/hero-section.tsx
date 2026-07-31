import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Cta {
    label: string;
    href: string;
    variant?: 'default' | 'outline' | 'secondary';
}

interface HeroSectionProps {
    title: React.ReactNode;
    description: string;
    primaryCta?: Cta;
    secondaryCta?: Cta;
}

export function HeroSection({ title, description, primaryCta, secondaryCta }: HeroSectionProps) {
    return (
        <section className="relative overflow-hidden px-4 pt-24 pb-20 md:pt-32 md:pb-28">
            <div className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-primary/10 via-transparent to-transparent" />
            <div className="mx-auto max-w-4xl text-center">
                <h1 className="text-4xl font-bold tracking-tight md:text-6xl">{title}</h1>
                <p className="mt-6 text-lg text-muted-foreground md:text-xl">{description}</p>
                {(primaryCta || secondaryCta) && (
                    <div className="mt-10 flex items-center justify-center gap-4">
                        {primaryCta && (
                            <Link href={primaryCta.href}>
                                <Button size="lg" variant={primaryCta.variant ?? 'default'}>
                                    {primaryCta.label}
                                </Button>
                            </Link>
                        )}
                        {secondaryCta && (
                            <Link href={secondaryCta.href}>
                                <Button size="lg" variant={secondaryCta.variant ?? 'outline'}>
                                    {secondaryCta.label}
                                </Button>
                            </Link>
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}
