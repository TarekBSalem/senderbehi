import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/vitrine/page-header';

export default function Privacy() {
    return (
        <>
            <Head title="Privacy Policy - SenderBehi">
                <meta name="description" content="SenderBehi privacy policy. Learn how we collect, use, and protect your data." />
            </Head>

            <PageHeader
                title="Privacy Policy"
                description="Last updated: July 2026"
            />

            <section className="mx-auto max-w-3xl px-4 pb-20">
                <div className="space-y-8 text-muted-foreground">
                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">1. Information We Collect</h2>
                        <p>We collect information you provide when creating an account, including your name and email address. We also collect data about your campaigns, recipients, and email performance metrics.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">2. How We Use Your Information</h2>
                        <p>We use your information to provide and improve our email marketing services, process transactions, send service updates, and provide customer support.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">3. Data Security</h2>
                        <p>We implement industry-standard security measures to protect your data. All data is encrypted in transit and at rest.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">4. Third-Party Services</h2>
                        <p>We may share data with third-party service providers who help us deliver our services. These providers are bound by strict data protection agreements.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">5. Your Rights</h2>
                        <p>You have the right to access, correct, or delete your personal data at any time. Contact us to exercise these rights.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">6. Contact</h2>
                        <p>For privacy-related inquiries, contact us at privacy@senderbehi.com.</p>
                    </div>
                </div>
            </section>
        </>
    );
}
