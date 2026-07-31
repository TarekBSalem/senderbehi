import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/vitrine/page-header';
import { PricingSection } from '@/components/vitrine/pricing-section';
import { CTASection } from '@/components/vitrine/cta-section';
import { register } from '@/routes';
import { toUrl } from '@/lib/utils';

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

export default function Pricing({ plans }: { plans: Plan[] }) {
    return (
        <>
            <Head title="Pricing - SenderBehi">
                <meta name="description" content="Simple, transparent pricing for email marketing. Choose from Free, Starter, Pro, or Business plans." />
            </Head>

            <PageHeader
                title="Simple, transparent pricing"
                description="Choose the plan that fits your needs. No hidden fees."
            />

            <PricingSection
                title=""
                plans={plans}
                className="pt-0"
            />

            <CTASection
                title="Ready to get started?"
                description="Join thousands of businesses using SenderBehi. No credit card required."
                cta={{ label: 'Start Free Trial', href: toUrl(register()) }}
                variant="muted"
            />
        </>
    );
}
