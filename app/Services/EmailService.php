<?php

namespace App\Services;

use App\Models\PlatformSmtpSetting;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

class EmailService
{
    /**
     * Send email using platform or user's custom SMTP
     */
    public function send(User $user, Mailable $mailable, string $email): bool
    {
        $userSmtp = $user->smtpSetting;

        if ($userSmtp && $userSmtp->is_custom) {
            return $this->sendWithCustomSmtp($userSmtp, $mailable, $email);
        }

        return $this->sendWithPlatformSmtp($mailable, $email);
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

        $transport = $this->createTransport(
            $platformSmtp->host,
            $platformSmtp->port,
            $platformSmtp->username,
            $platformSmtp->password,
            $platformSmtp->encryption
        );

        Mail::transport($transport)
            ->to($email)
            ->send($mailable);

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

        Mail::transport($transport)
            ->to($email)
            ->send($mailable);

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
    ): SmtpTransport {
        return new SmtpTransport(
            $host,
            $port,
            $encryption === 'ssl' ? 'ssl' : 'tls',
            [
                'username' => $username,
                'password' => $password,
            ]
        );
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
