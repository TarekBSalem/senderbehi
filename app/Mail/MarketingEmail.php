<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Mail\Message;
use Illuminate\Queue\SerializesModels;

class MarketingEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $htmlContent,
        string $subject,
        public string $unsubscribeUrl = '',
        public ?string $returnPath = null,
    ) {
        $this->subject = $subject;
    }

    public function envelope(): Envelope
    {
        $using = $this->returnPath
            ? [fn (Message $message) => $message->returnPath($this->returnPath)]
            : [];

        return new Envelope(
            subject: $this->subject,
            using: $using,
        );
    }

    public function headers(): Headers
    {
        $headers = new Headers(
            text: [
                'List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );

        return $headers;
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlContent,
        );
    }
}
