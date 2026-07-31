import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/vitrine/page-header';

export default function Terms() {
    return (
        <>
            <Head title="Terms of Service - SenderBehi">
                <meta name="description" content="SenderBehi terms of service. Please read these terms carefully before using our platform." />
            </Head>

            <PageHeader
                title="Terms of Service"
                description="Last updated: July 2026"
            />

            <section className="mx-auto max-w-3xl px-4 pb-20">
                <div className="space-y-8 text-muted-foreground">
                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">1. Acceptance of Terms</h2>
                        <p>By accessing and using SenderBehi, you agree to be bound by these Terms of Service. If you do not agree, do not use the service.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">2. Service Description</h2>
                        <p>SenderBehi provides an email marketing platform that allows users to create, send, and track email campaigns. We reserve the right to modify or discontinue the service at any time.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">3. User Obligations</h2>
                        <p>You agree not to use SenderBehi for sending spam, illegal content, or any material that violates applicable laws. You are responsible for maintaining the confidentiality of your account.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">4. Payment Terms</h2>
                        <p>Paid plans are billed monthly or annually as selected. Cancellation takes effect at the end of the current billing period. No refunds for partial months.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">5. Limitation of Liability</h2>
                        <p>SenderBehi is provided "as is" without warranty. We are not liable for any damages arising from the use of our service.</p>
                    </div>

                    <div>
                        <h2 className="mb-4 text-xl font-semibold text-foreground">6. Contact</h2>
                        <p>For questions about these terms, contact us at legal@senderbehi.com.</p>
                    </div>
                </div>
            </section>
        </>
    );
}
