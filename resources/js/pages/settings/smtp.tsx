import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import Heading from '@/components/heading';
import ResultDialog from '@/components/result-dialog';
import { useState } from 'react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';

interface SmtpSettings {
    is_custom: boolean;
    host: string;
    port: number;
    username: string;
    encryption: string;
    from_email: string;
    from_name: string;
    sends_per_minute: number;
    is_active: boolean;
}

interface Props {
    settings: SmtpSettings;
}

export default function SmtpSettings({ settings }: Props) {
    const [isCustom, setIsCustom] = useState(settings.is_custom);
    const [host, setHost] = useState(settings.host);
    const [port, setPort] = useState(String(settings.port));
    const [username, setUsername] = useState(settings.username);
    const [password, setPassword] = useState('');
    const [encryption, setEncryption] = useState(settings.encryption);
    const [fromEmail, setFromEmail] = useState(settings.from_email);
    const [fromName, setFromName] = useState(settings.from_name);
    const [sendsPerMinute, setSendsPerMinute] = useState(String(settings.sends_per_minute));
    const [showPassword, setShowPassword] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);
    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        router.put('/settings/smtp', {
            is_custom: isCustom,
            host: isCustom ? host : null,
            port: isCustom ? Number(port) : null,
            username: isCustom ? username : null,
            password: isCustom ? (password || undefined) : null,
            encryption: isCustom ? encryption : null,
            from_email: isCustom ? fromEmail : null,
            from_name: isCustom ? fromName : null,
            sends_per_minute: isCustom ? Number(sendsPerMinute) : null,
        }, {
            onError: (errs) => setErrors(errs),
            onFinish: () => setSubmitting(false),
        });
    };

    const testConnection = async () => {
        setTesting(true);
        try {
            const res = await fetch('/settings/smtp/test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '' },
                body: JSON.stringify({ host, port: Number(port) }),
            });
            const data = await res.json();
            setTestResult({
                success: data.success,
                message: data.success ? (data.message || 'Connection successful.') : (data.message || 'Unknown error'),
            });
        } catch (err) {
            setTestResult({ success: false, message: 'Connection test failed.' });
        } finally {
            setTesting(false);
        }
    };

    return (
        <>
            <Head title="SMTP Settings" />

            <Heading
                variant="small"
                title="SMTP Settings"
                description="Configure your own SMTP server to send emails from your campaigns"
            />

            <form onSubmit={submit} className="space-y-6">
                <div className="flex items-center gap-3">
                    <Checkbox id="is_custom" checked={isCustom} onCheckedChange={(v) => setIsCustom(v === true)} />
                    <Label htmlFor="is_custom">Use custom SMTP server</Label>
                </div>

                {isCustom && (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="host">SMTP Host</Label>
                            <Input
                                id="host"
                                value={host}
                                onChange={(e) => setHost(e.target.value)}
                                placeholder="smtp.example.com"
                                className={errors.host ? 'border-destructive' : ''}
                            />
                            {errors.host && <p className="text-sm text-destructive">{errors.host}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="port">Port</Label>
                            <Input
                                id="port"
                                type="number"
                                value={port}
                                onChange={(e) => setPort(e.target.value)}
                                placeholder="587"
                                className={errors.port ? 'border-destructive' : ''}
                            />
                            {errors.port && <p className="text-sm text-destructive">{errors.port}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="encryption">Encryption</Label>
                            <Select value={encryption} onValueChange={setEncryption}>
                                <SelectTrigger id="encryption">
                                    <SelectValue placeholder="Select encryption" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="tls">TLS</SelectItem>
                                    <SelectItem value="ssl">SSL</SelectItem>
                                    <SelectItem value="none">None</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.encryption && <p className="text-sm text-destructive">{errors.encryption}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="username">Username</Label>
                            <Input
                                id="username"
                                value={username}
                                onChange={(e) => setUsername(e.target.value)}
                                placeholder="user@example.com"
                                className={errors.username ? 'border-destructive' : ''}
                            />
                            {errors.username && <p className="text-sm text-destructive">{errors.username}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <div className="relative">
                                <Input
                                    id="password"
                                    type={showPassword ? 'text' : 'password'}
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder={settings.is_custom ? 'Leave blank to keep current' : 'Enter password'}
                                    className={errors.password ? 'border-destructive pr-10' : 'pr-10'}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                >
                                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                </button>
                            </div>
                            {errors.password && <p className="text-sm text-destructive">{errors.password}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="from_email">From Email</Label>
                            <Input
                                id="from_email"
                                type="email"
                                value={fromEmail}
                                onChange={(e) => setFromEmail(e.target.value)}
                                placeholder="noreply@example.com"
                                className={errors.from_email ? 'border-destructive' : ''}
                            />
                            {errors.from_email && <p className="text-sm text-destructive">{errors.from_email}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="from_name">From Name</Label>
                            <Input
                                id="from_name"
                                value={fromName}
                                onChange={(e) => setFromName(e.target.value)}
                                placeholder="My Company"
                                className={errors.from_name ? 'border-destructive' : ''}
                            />
                            {errors.from_name && <p className="text-sm text-destructive">{errors.from_name}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="sends_per_minute">Sends Per Minute</Label>
                            <Input
                                id="sends_per_minute"
                                type="number"
                                value={sendsPerMinute}
                                onChange={(e) => setSendsPerMinute(e.target.value)}
                                min={1}
                                max={600}
                                className={errors.sends_per_minute ? 'border-destructive' : ''}
                            />
                            <p className="text-sm text-muted-foreground">Maximum emails per minute (1-600)</p>
                            {errors.sends_per_minute && <p className="text-sm text-destructive">{errors.sends_per_minute}</p>}
                        </div>
                    </>
                )}

                <div className="flex items-center gap-4">
                    <Button type="submit" disabled={submitting}>
                        {submitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Save
                    </Button>
                    {isCustom && (
                        <Button type="button" variant="outline" onClick={testConnection} disabled={testing}>
                            {testing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Test Connection
                        </Button>
                    )}
                </div>
            </form>

            <ResultDialog
                open={testResult !== null}
                onOpenChange={(open) => !open && setTestResult(null)}
                variant={testResult?.success ? 'success' : 'error'}
                title={testResult?.success ? 'Connection Successful' : 'Connection Failed'}
                message={testResult?.message ?? ''}
            />
        </>
    );
}

SmtpSettings.layout = {
    breadcrumbs: [
        { title: 'SMTP Settings', href: '/settings/smtp' },
    ],
};
