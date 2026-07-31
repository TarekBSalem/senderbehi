import { SectionHeader } from '@/components/vitrine/section-header';
import { cn } from '@/lib/utils';

interface Testimonial {
    quote: string;
    author: string;
    role: string;
}

interface TestimonialsSectionProps {
    title: string;
    subtitle?: string;
    testimonials: Testimonial[];
    className?: string;
}

export function TestimonialsSection({ title, subtitle, testimonials, className }: TestimonialsSectionProps) {
    return (
        <section className={cn('border-t bg-card px-4 py-20', className)}>
            <div className="mx-auto max-w-7xl">
                <SectionHeader title={title} subtitle={subtitle} />
                <div className="mt-16 grid gap-8 md:grid-cols-3">
                    {testimonials.map((t) => (
                        <div key={t.author} className="rounded-lg border bg-background p-6 shadow-sm">
                            <p className="text-muted-foreground italic">"{t.quote}"</p>
                            <div className="mt-6 border-t pt-4">
                                <div className="font-semibold">{t.author}</div>
                                <div className="text-sm text-muted-foreground">{t.role}</div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
