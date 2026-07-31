import { Head, Link, router } from '@inertiajs/react';
import subscriptionPlans from '@/routes/admin/subscription-plans';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { useState } from 'react';

interface PlanDetail {
    id: number;
    name: string;
    code: string;
    stripe_price_id: string | null;
    price_cents: number;
    currency: string;
    email_limit: number;
    features: Record<string, boolean | number> | null;
    sort_order: number;
    is_active: boolean;
    created_at: string;
}

interface Props {
    plan: PlanDetail;
}

export default function AdminSubscriptionPlansEdit({ plan }: Props) {
    const [name, setName] = useState(plan.name);
    const [code, setCode] = useState(plan.code);
    const [priceCents, setPriceCents] = useState(String(plan.price_cents));
    const [currency, setCurrency] = useState(plan.currency);
    const [emailLimit, setEmailLimit] = useState(String(plan.email_limit));
    const [sortOrder, setSortOrder] = useState(String(plan.sort_order));
    const [isActive, setIsActive] = useState(plan.is_active);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        router.put(subscriptionPlans.update.url({ plan: plan.id }), {
            name,
            code,
            price_cents: parseInt(priceCents, 10) || 0,
            currency,
            email_limit: parseInt(emailLimit, 10) || 0,
            sort_order: sortOrder ? parseInt(sortOrder, 10) : undefined,
            is_active: isActive,
        }, {
            onError: (errs) => setErrors(errs),
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <>
            <Head title={`Edit ${plan.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <Link href={subscriptionPlans.index.url()} className="text-sm text-muted-foreground hover:underline">
                        &larr; Back to plans
                    </Link>
                    <h1 className="text-2xl font-bold mt-2">Edit Plan</h1>
                    <p className="text-muted-foreground">{plan.name}</p>
                </div>

                <Card className="max-w-lg">
                    <CardHeader>
                        <CardTitle>Plan Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    className={errors.name ? 'border-destructive' : ''}
                                />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="code">Code</Label>
                                <Input
                                    id="code"
                                    placeholder="e.g. starter, pro, business"
                                    value={code}
                                    onChange={(e) => setCode(e.target.value)}
                                    className={errors.code ? 'border-destructive' : ''}
                                />
                                {errors.code && <p className="text-sm text-destructive">{errors.code}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="priceCents">Price (cents)</Label>
                                    <Input
                                        id="priceCents"
                                        type="number"
                                        min="0"
                                        placeholder="e.g. 2900 = $29"
                                        value={priceCents}
                                        onChange={(e) => setPriceCents(e.target.value)}
                                        className={errors.price_cents ? 'border-destructive' : ''}
                                    />
                                    {errors.price_cents && <p className="text-sm text-destructive">{errors.price_cents}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <Select value={currency} onValueChange={setCurrency}>
                                        <SelectTrigger id="currency" className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="usd">USD</SelectItem>
                                            <SelectItem value="eur">EUR</SelectItem>
                                            <SelectItem value="gbp">GBP</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.currency && <p className="text-sm text-destructive">{errors.currency}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="emailLimit">Email Limit</Label>
                                <Input
                                    id="emailLimit"
                                    type="number"
                                    min="0"
                                    placeholder="e.g. 5000"
                                    value={emailLimit}
                                    onChange={(e) => setEmailLimit(e.target.value)}
                                    className={errors.email_limit ? 'border-destructive' : ''}
                                />
                                {errors.email_limit && <p className="text-sm text-destructive">{errors.email_limit}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sortOrder">Sort Order</Label>
                                <Input
                                    id="sortOrder"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    value={sortOrder}
                                    onChange={(e) => setSortOrder(e.target.value)}
                                />
                            </div>

                            <div className="flex items-center gap-3 pt-2">
                                <Checkbox id="isActive" checked={isActive} onCheckedChange={(v) => setIsActive(v === true)} />
                                <Label htmlFor="isActive">Active</Label>
                            </div>

                            <div className="flex gap-3 pt-2">
                                <Button type="submit" disabled={submitting}>
                                    {submitting ? 'Saving...' : 'Save Changes'}
                                </Button>
                                <Link href={subscriptionPlans.index.url()}>
                                    <Button type="button" variant="outline">Cancel</Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminSubscriptionPlansEdit.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/users' },
        { title: 'Plans', href: '/admin/subscription-plans' },
        { title: 'Edit', href: '' },
    ],
};
