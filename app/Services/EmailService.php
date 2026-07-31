<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\PlatformSmtpSetting;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class EmailService
{
    /**
     * Send email using campaign, user, or platform SMTP (in that order of priority)
     */
    public function send(User $user, Mailable $mailable, string $email, ?Campaign $campaign = null): bool
    {
        if ($campaign && $campaign->use_custom_smtp && $campaign->smtp_host) {
            return $this->sendWithCampaignSmtp($campaign, $mailable, $email);
        }

        $userSmtp = $user->smtpSetting;

        if ($userSmtp && $userSmtp->is_custom) {
            return $this->sendWithCustomSmtp($userSmtp, $mailable, $email);
        }

        return $this->sendWithPlatformSmtp($mailable, $email);
    }

    /**
     * Send using the campaign's own SMTP (per-campaign override)
     */
    private function sendWithCampaignSmtp(Campaign $campaign, Mailable $mailable, string $email): bool
    {
        $transport = $this->createTransport(
            $campaign->smtp_host,
            $campaign->smtp_port ?? 587,
            $campaign->smtp_username ?? '',
            $campaign->smtp_password ?? '',
            $campaign->smtp_encryption ?? 'tls'
        );

        if ($campaign->smtp_from_email) {
            $mailable->from($campaign->smtp_from_email, $campaign->smtp_from_name);
        }

        $mailer = Mail::mailer();
        $mailer->setSymfonyTransport($transport);
        $mailer->to($email)->send($mailable);

        return true;
    }

    /**
     * Send using platform's SMTP (admin-configured)
     */
    private function sendWithPlatformSmtp(Mailable $mailable, string $email): bool
    {
        $platformSmtp = PlatformSmtpSetting::where('is_active', true)->first();

        if (! $platformSmtp) {
            throw new \Exception('Platform SMTP not configured. Please ask admin to configure SMTP settings.');
        }

        if ($platformSmtp->dailyLimitReached()) {
            throw new \Exception('Platform SMTP daily limit of '.$platformSmtp->daily_limit.' emails has been reached. Sending will resume tomorrow.');
        }

        $transport = $this->createTransport(
            $platformSmtp->host,
            $platformSmtp->port,
            $platformSmtp->username,
            $platformSmtp->password,
            $platformSmtp->encryption
        );

        $mailer = Mail::mailer();
        $mailer->setSymfonyTransport($transport);
        $mailer->to($email)->send($mailable);

        $platformSmtp->incrementDailySentCount();

        return true;
    }

    /**
     * Send using user's custom SMTP
     */
    private function sendWithCustomSmtp(SmtpSetting $smtp, Mailable $mailable, string $email): bool
    {
        $transport = $this->createTransport(
            $smtp->host,
            $smtp->port,
            $smtp->username,
            $smtp->password,
            $smtp->encryption
        );

        $mailable->from($smtp->from_email, $smtp->from_name);

        $mailer = Mail::mailer();
        $mailer->setSymfonyTransport($transport);
        $mailer->to($email)->send($mailable);

        return true;
    }

    /**
     * Create SMTP transport
     */
    private function createTransport(
        string $host,
        int $port,
        string $username,
        string $password,
        string $encryption
    ): EsmtpTransport {
        $transport = new EsmtpTransport($host, $port, $encryption === 'ssl');
        $transport->setUsername($username);
        $transport->setPassword($password);

        return $transport;
    }

    /**
     * Test SMTP connection
     */
    public function testConnection(string $host, int $port): bool
    {
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);

        if (! $socket) {
            throw new \Exception("Could not connect: $errstr ($errno)");
        }

        fclose($socket);

        return true;
    }
}
