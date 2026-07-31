import { Head } from '@inertiajs/react';
import { register } from '@/routes';
import { HeroSection } from '@/components/vitrine/hero-section';
import { FeaturesSection } from '@/components/vitrine/features-section';
import { StatsSection } from '@/components/vitrine/stats-section';
import { TestimonialsSection } from '@/components/vitrine/testimonials-section';
import { CTASection } from '@/components/vitrine/cta-section';
import { toUrl } from '@/lib/utils';
import { Mail, Clock, BarChart3, Server, Upload, Split } from 'lucide-react';

const features = [
    { title: 'Drag-and-Drop Templates', description: 'Create beautiful emails with our intuitive template builder. No coding required.', icon: <Mail className="size-6 text-primary" /> },
    { title: 'Smart Scheduling', description: 'Schedule campaigns to send at the optimal time for your audience. Set it and forget it.', icon: <Clock className="size-6 text-primary" /> },
    { title: 'Detailed Analytics', description: 'Track opens, clicks, and conversions in real time. Know exactly what works.', icon: <BarChart3 className="size-6 text-primary" /> },
    { title: 'Custom SMTP', description: 'Use your own SMTP server for complete control over deliverability and sending reputation.', icon: <Server className="size-6 text-primary" /> },
    { title: 'CSV Import', description: 'Import your subscriber lists from CSV files with simple column mapping.', icon: <Upload className="size-6 text-primary" /> },
    { title: 'A/B Testing', description: 'Test subject lines, content, and send times to optimize every campaign.', icon: <Split className="size-6 text-primary" /> },
];

const stats = [
    { value: '10K+', label: 'Active Users' },
    { value: '50M+', label: 'Emails Sent' },
    { value: '99.9%', label: 'Deliverability' },
    { value: '4.9', label: 'Average Rating' },
];

const testimonials = [
    { quote: 'SenderBehi transformed how we reach our customers. The analytics alone are worth it.', author: 'Sarah Chen', role: 'Marketing Director, TechFlow' },
    { quote: 'We switched from Mailchimp and saved 60%. The templates are gorgeous.', author: 'James Wilson', role: 'Founder, BrightPath Media' },
    { quote: 'The deliverability is outstanding. Our open rates went up 25%.', author: 'Maria Rodriguez', role: 'CEO, GrowWithEmail' },
];

export default function Welcome() {
    return (
        <>
            <Head title="SenderBehi - Email Marketing Made Simple">
                <meta name="description" content="Build, send, and track beautiful marketing email campaigns with SenderBehi. Drag-and-drop templates, smart scheduling, and detailed analytics." />
            </Head>

            <HeroSection
                title={<>Send Emails That <span className="text-primary">Get Results</span></>}
                description="Build, send, and track beautiful marketing campaigns with SenderBehi. No coding required."
                primaryCta={{ label: 'Start Free Trial', href: toUrl(register()) }}
                secondaryCta={{ label: 'See Pricing', href: '/pricing' }}
            />

            <StatsSection stats={stats} />

            <FeaturesSection
                title="Everything you need to succeed"
                subtitle="Powerful features that make email marketing simple and effective."
                features={features}
                variant="muted"
            />

            <TestimonialsSection
                title="Trusted by thousands"
                subtitle="See what our customers have to say about SenderBehi."
                testimonials={testimonials}
            />

            <CTASection
                title="Ready to get started?"
                description="Start sending better emails today. No credit card required."
                cta={{ label: 'Start Free Trial', href: toUrl(register()) }}
            />
        </>
    );
}
