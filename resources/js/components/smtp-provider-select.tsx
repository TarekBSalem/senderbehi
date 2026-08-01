import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { SMTP_PROVIDERS, type SmtpProviderPreset } from '@/lib/smtp-providers';

interface SmtpProviderSelectProps {
    value: string;
    onSelect: (provider: SmtpProviderPreset) => void;
}

export default function SmtpProviderSelect({ value, onSelect }: SmtpProviderSelectProps) {
    const selected = SMTP_PROVIDERS.find((provider) => provider.id === value);

    return (
        <div className="grid gap-2">
            <Label>Provider</Label>
            <Select
                value={value}
                onValueChange={(id) => {
                    const preset = SMTP_PROVIDERS.find((provider) => provider.id === id);
                    if (preset) {
                        onSelect(preset);
                    }
                }}
            >
                <SelectTrigger>
                    <SelectValue placeholder="Select provider" />
                </SelectTrigger>
                <SelectContent>
                    {SMTP_PROVIDERS.map((provider) => (
                        <SelectItem key={provider.id} value={provider.id}>
                            {provider.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {selected?.hint && <p className="text-sm text-muted-foreground">{selected.hint}</p>}
        </div>
    );
}
