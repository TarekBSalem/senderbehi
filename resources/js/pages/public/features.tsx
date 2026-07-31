import { Head } from '@inertiajs/react';
import { register } from '@/routes';
import { PageHeader } from '@/components/vitrine/page-header';
import { FeaturesSection } from '@/components/vitrine/features-section';
import { CTASection } from '@/components/vitrine/cta-section';
import { toUrl } from '@/lib/utils';
import { Mail, Clock, BarChart3, Server, Upload, Split, Shield, Users, Zap } from 'lucide-react';

const features = [
    { title: 'Drag-and-Drop Templates', description: 'Create beautiful emails with our intuitive template builder. Choose from professionally designed templates and customize them in minutes.', icon: <Mail className="size-6 text-primary" /> },
    { title: 'Smart Scheduling', description: 'Schedule campaigns to send at the perfect time for maximum engagement. Set it and forget it.', icon: <Clock className="size-6 text-primary" /> },
    { title: 'Detailed Analytics', description: 'Track opens, clicks, and conversions with real-time reporting. Know exactly what resonates with your audience.', icon: <BarChart3 className="size-6 text-primary" /> },
    { title: 'Custom SMTP', description: 'Use your own SMTP server for complete control over deliverability and sending reputation.', icon: <Server className="size-6 text-primary" /> },
    { title: 'CSV Import', description: 'Import your subscriber lists from CSV files with ease. Simple column mapping makes it effortless.', icon: <Upload className="size-6 text-primary" /> },
    { title: 'A/B Testing', description: 'Test subject lines, content, and send times to optimize every campaign for better results.', icon: <Split className="size-6 text-primary" /> },
    { title: 'Open & Click Tracking', description: 'Track every open and click with our built-in tracking pixel and link wrapping technology.', icon: <Shield className="size-6 text-primary" /> },
    { title: 'Team Collaboration', description: 'Invite team members and collaborate on campaigns with role-based access control.', icon: <Users className="size-6 text-primary" /> },
    { title: 'High-Speed Sending', description: 'Send thousands of emails per hour with our optimized queue-based sending pipeline.', icon: <Zap className="size-6 text-primary" /> },
];

export default function Features({ features: _features }: { features: { title: string; description: string; icon: string }[] }) {
    return (
        <>
            <Head title="Features - SenderBehi">
                <meta name="description" content="Discover powerful email marketing features: drag-and-drop templates, smart scheduling, detailed analytics, custom SMTP, and more." />
            </Head>

            <PageHeader
                title="Powerful features for email marketing"
                description="Everything you need to create, send, and optimize your campaigns."
            />

            <FeaturesSection
                title=""
                features={features}
                columns={3}
                className="pt-0"
            />

            <CTASection
                title="Ready to try SenderBehi?"
                description="Start your free trial today. No credit card required."
                cta={{ label: 'Start Free Trial', href: toUrl(register()) }}
                variant="muted"
            />
        </>
    );
}
