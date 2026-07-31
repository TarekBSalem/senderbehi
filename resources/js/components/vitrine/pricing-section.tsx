import { SectionHeader } from '@/components/vitrine/section-header';
import { PricingCard } from '@/components/vitrine/pricing-card';
import { cn } from '@/lib/utils';

interface Plan {
    id: number;
    name: string;
    code: string;
    price_cents: number;
    formatted_price: string;
    email_limit: number;
    formatted_email_limit: string;
    features: Record<string, boolean | number>;
    is_free: boolean;
}

interface PricingSectionProps {
    title: string;
    subtitle?: string;
    plans: Plan[];
    className?: string;
}

export function PricingSection({ title, subtitle, plans, className }: PricingSectionProps) {
    const highlightedPlan = plans.find((p) => p.code === 'pro');

    return (
        <section className={cn('px-4 py-20', className)}>
            <div className="mx-auto max-w-7xl">
                {title && <SectionHeader title={title} subtitle={subtitle} />}
                <div className={cn('grid gap-8 md:grid-cols-4', title ? 'mt-16' : '')}>
                    {plans.map((plan) => (
                        <PricingCard
                            key={plan.id}
                            name={plan.name}
                            price={plan.formatted_price}
                            description={`${plan.formatted_email_limit} emails per month`}
                            features={plan.features}
                            cta={{ label: plan.is_free ? 'Get Started' : 'Subscribe', href: '/register' }}
                            highlighted={!plan.is_free && highlightedPlan?.id === plan.id}
                            isFree={plan.is_free}
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}
