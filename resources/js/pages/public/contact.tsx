import { Head } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { PageHeader } from '@/components/vitrine/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import contact from '@/routes/contact';

export default function Contact() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        message: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(contact.send.url(), {
            onSuccess: () => reset(),
        });
    }

    return (
        <>
            <Head title="Contact - SenderBehi">
                <meta name="description" content="Get in touch with the SenderBehi team. We're here to help with any questions." />
            </Head>

            <PageHeader
                title="Contact us"
                description="Have a question? We'd love to hear from you."
            />

            <section className="mx-auto max-w-2xl px-4 pb-20">
                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Your name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="you@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="message">Message</Label>
                        <textarea
                            id="message"
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            placeholder="How can we help?"
                            rows={6}
                            className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={errors.message} />
                    </div>

                    <Button type="submit" disabled={processing} className="w-full">
                        Send message
                    </Button>
                </form>
            </section>
        </>
    );
}
