export interface SmtpProviderPreset {
    id: string;
    label: string;
    host: string;
    port: number;
    encryption: string;
    username?: string;
    hint?: string;
}

export const SMTP_PROVIDERS: SmtpProviderPreset[] = [
    {
        id: 'custom',
        label: 'Custom',
        host: '',
        port: 587,
        encryption: 'tls',
        hint: 'Enter your own SMTP server details.',
    },
    {
        id: 'gmail',
        label: 'Gmail / Google Workspace',
        host: 'smtp.gmail.com',
        port: 587,
        encryption: 'tls',
        hint: 'Use an app-specific password, not your regular Gmail password.',
    },
    {
        id: 'o2switch',
        label: 'o2switch',
        host: 'smtp.o2switch.fr',
        port: 465,
        encryption: 'ssl',
        hint: 'Shared-hosting relay — fine for transactional mail, not ideal for bulk campaigns.',
    },
    {
        id: 'mailgun',
        label: 'Mailgun',
        host: 'smtp.mailgun.org',
        port: 587,
        encryption: 'tls',
        hint: 'Verify your sending domain in the Mailgun dashboard and add their DNS records.',
    },
    {
        id: 'sendgrid',
        label: 'SendGrid',
        host: 'smtp.sendgrid.net',
        port: 587,
        encryption: 'tls',
        username: 'apikey',
        hint: 'Username is always "apikey". Password is your SendGrid API key.',
    },
    {
        id: 'postmark',
        label: 'Postmark',
        host: 'smtp.postmarkapp.com',
        port: 587,
        encryption: 'tls',
        hint: 'Username and password are both your Postmark Server API token.',
    },
    {
        id: 'ses',
        label: 'Amazon SES',
        host: 'email-smtp.us-east-1.amazonaws.com',
        port: 587,
        encryption: 'tls',
        hint: 'Create SMTP credentials in the SES console. Adjust the region in the host if needed.',
    },
];

const HOST_HINTS: Array<[string, string]> = [
    ['postmark', 'postmarkapp.com'],
    ['sendgrid', 'sendgrid.net'],
    ['mailgun', 'mailgun.org'],
    ['ses', '.amazonaws.com'],
    ['gmail', 'smtp.gmail.com'],
    ['o2switch', 'o2switch.fr'],
];

export function detectSmtpProvider(host: string): string {
    const normalized = (host || '').toLowerCase();

    for (const [id, hint] of HOST_HINTS) {
        if (normalized.includes(hint)) {
            return id;
        }
    }

    return 'custom';
}
