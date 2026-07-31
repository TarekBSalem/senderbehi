<?php

namespace App\Console\Commands;

use App\Services\BounceService;
use Illuminate\Console\Command;

class FetchBounces extends Command
{
    protected $signature = 'bounces:fetch';

    protected $description = 'Poll bounce mailbox via IMAP and process bounce notifications';

    public function handle(BounceService $bounceService): int
    {
        if (! function_exists('imap_open')) {
            $this->error('PHP IMAP extension is not installed. Install php-imap or use the webhook endpoint instead.');

            return self::FAILURE;
        }

        $host = config('services.bounce.imap_host');
        $port = config('services.bounce.imap_port', 993);
        $encryption = config('services.bounce.imap_encryption', 'ssl');
        $username = config('services.bounce.imap_username');
        $password = config('services.bounce.imap_password');
        $mailbox = config('services.bounce.imap_mailbox', 'INBOX');

        if (! $host || ! $username || ! $password) {
            $this->error('Bounce mailbox not configured. Set BOUNCE_IMAP_* env vars.');

            return self::FAILURE;
        }

        $mailboxPath = "{{$host}:{$port}/imap/{$encryption}}{$mailbox}";

        $inbox = @imap_open($mailboxPath, $username, $password);

        if (! $inbox) {
            $this->error('Could not connect to bounce mailbox: '.imap_last_error());

            return self::FAILURE;
        }

        $emails = imap_search($inbox, 'UNSEEN');

        if (! $emails) {
            imap_close($inbox);

            $this->info('No new bounce emails found.');

            return self::SUCCESS;
        }

        $processed = 0;

        foreach ($emails as $msgId) {
            $body = imap_fetchbody($inbox, $msgId, '1');
            $headers = imap_headerinfo($inbox, $msgId);

            $email = $this->extractBouncedEmail($body, $headers);

            if ($email) {
                $bounceService->processHardBounce($email);
                $processed++;
            }

            imap_delete($inbox, $msgId);
        }

        imap_expunge($inbox);
        imap_close($inbox);

        $this->info("Processed {$processed} bounce notifications.");

        return self::SUCCESS;
    }

    private function extractBouncedEmail(string $body, object $headers): ?string
    {
        $patterns = [
            '/Original-Recipient:\s*rfc822[;:]\s*(\S+@\S+)/i',
            '/Final-Recipient:\s*rfc822[;:]\s*(\S+@\S+)/i',
            '/X-Failed-Recipients:\s*(\S+@\S+)/i',
            '/<(\S+@\S+)>/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                return filter_var(trim($matches[1]), FILTER_VALIDATE_EMAIL) ?: null;
            }
        }

        if (isset($headers->to) && count($headers->to) > 0) {
            $to = $headers->to[0]->mailbox.'@'.$headers->to[0]->host;

            return filter_var($to, FILTER_VALIDATE_EMAIL) ?: null;
        }

        return null;
    }
}
