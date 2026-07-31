import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Loader2 } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ResultDialog from '@/components/result-dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface SmtpSettings {
    id: number | null;
    host: string;
    port: number;
    username: string;
    encryption: string;
    from_email: string;
    from_name: string;
    daily_limit: number;
    is_active: boolean;
    has_password: boolean;
}

interface Props {
    smtp: SmtpSettings | null;
}

export default function AdminSmtpSettings({ smtp }: Props) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        host: smtp?.host || '',
        port: smtp?.port || 587,
        username: smtp?.username || '',
        password: '',
        encryption: smtp?.encryption || 'tls',
        from_email: smtp?.from_email || '',
        from_name: smtp?.from_name || '',
        daily_limit: smtp?.daily_limit || 100,
        is_active: smtp?.is_active ?? true,
    });
    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/admin/smtp');
    };

    const handleTestConnection = async () => {
        setTesting(true);

        try {
            const res = await fetch('/admin/smtp/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    host: data.host,
                    port: data.port,
                    encryption: data.encryption,
                }),
            });
            const body = await res.json();

            setTestResult({
                success: body.success,
                message: body.success ? (body.message || 'Connection successful.') : (body.error || body.message || 'Connection failed.'),
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
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-bold">Platform SMTP Settings</h1>
                    <p className="text-muted-foreground">
                        Configure the SMTP server used for sending emails
                    </p>
                </div>

                {recentlySuccessful && (
                    <Alert>
                        <AlertDescription>SMTP settings saved successfully.</AlertDescription>
                    </Alert>
                )}

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>SMTP Configuration</CardTitle>
                        <CardDescription>
                            Configure your Gmail SMTP settings for sending emails
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="host">SMTP Host</Label>
                                    <Input
                                        id="host"
                                        value={data.host}
                                        onChange={(e) => setData('host', e.target.value)}
                                        placeholder="smtp.gmail.com"
                                    />
                                    {errors.host && (
                                        <p className="text-sm text-red-500">{errors.host}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="port">Port</Label>
                                    <Input
                                        id="port"
                                        type="number"
                                        value={data.port}
                                        onChange={(e) => setData('port', parseInt(e.target.value))}
                                    />
                                    {errors.port && (
                                        <p className="text-sm text-red-500">{errors.port}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="username">Username (Email)</Label>
                                    <Input
                                        id="username"
                                        value={data.username}
                                        onChange={(e) => setData('username', e.target.value)}
                                        placeholder="your-email@gmail.com"
                                    />
                                    {errors.username && (
                                        <p className="text-sm text-red-500">{errors.username}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password">
                                        Password {smtp?.has_password && '(Leave blank to keep current)'}
                                    </Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="App-specific password"
                                    />
                                    {errors.password && (
                                        <p className="text-sm text-red-500">{errors.password}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="encryption">Encryption</Label>
                                <Select
                                    value={data.encryption}
                                    onValueChange={(value) => setData('encryption', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="tls">TLS (Port 587)</SelectItem>
                                        <SelectItem value="ssl">SSL (Port 465)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="from_email">From Email</Label>
                                    <Input
                                        id="from_email"
                                        type="email"
                                        value={data.from_email}
                                        onChange={(e) => setData('from_email', e.target.value)}
                                        placeholder="noreply@example.com"
                                    />
                                    {errors.from_email && (
                                        <p className="text-sm text-red-500">{errors.from_email}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="from_name">From Name</Label>
                                    <Input
                                        id="from_name"
                                        value={data.from_name}
                                        onChange={(e) => setData('from_name', e.target.value)}
                                        placeholder="My Company"
                                    />
                                    {errors.from_name && (
                                        <p className="text-sm text-red-500">{errors.from_name}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="daily_limit">Daily Send Limit</Label>
                                <Input
                                    id="daily_limit"
                                    type="number"
                                    value={data.daily_limit}
                                    onChange={(e) => setData('daily_limit', parseInt(e.target.value))}
                                    min="1"
                                    max="100000"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Gmail free accounts: 500/day, Workspace: 2,000/day
                                </p>
                            </div>

                            <div className="flex gap-4 pt-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Settings'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleTestConnection}
                                    disabled={processing || testing}
                                >
                                    {testing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    Test Connection
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Gmail SMTP Setup Guide</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm">
                        <ol className="list-decimal list-inside space-y-2">
                            <li>Go to your Google Account settings</li>
                            <li>Navigate to Security → 2-Step Verification (enable if not already)</li>
                            <li>Go to Security → App passwords</li>
                            <li>Generate a new app password for "Mail"</li>
                            <li>Copy the 16-character password</li>
                            <li>Paste it in the password field above</li>
                        </ol>
                        <p className="text-muted-foreground">
                            <strong>Note:</strong> Never use your regular Gmail password. Always use an app-specific password.
                        </p>
                    </CardContent>
                </Card>
            </div>

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

AdminSmtpSettings.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin' },
        { title: 'SMTP Settings', href: '/admin/smtp' },
    ],
};
