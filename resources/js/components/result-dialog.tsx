import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { CheckCircle2, XCircle } from 'lucide-react';

interface ResultDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    variant: 'success' | 'error';
    title: string;
    message: string;
}

export default function ResultDialog({ open, onOpenChange, variant, title, message }: ResultDialogProps) {
    const Icon = variant === 'success' ? CheckCircle2 : XCircle;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <div className="flex items-center gap-2">
                        <Icon className={variant === 'success' ? 'h-5 w-5 text-emerald-600' : 'h-5 w-5 text-destructive'} />
                        <DialogTitle>{title}</DialogTitle>
                    </div>
                    <DialogDescription className="whitespace-pre-wrap">{message}</DialogDescription>
                </DialogHeader>
            </DialogContent>
        </Dialog>
    );
}
