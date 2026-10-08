<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PublicDemandAcknowledgementMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $trackingNumber,
        public readonly string $subjectLabel,
        public readonly string $recipientName,
        public readonly int $maxProcessingHours = 72
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ANBG - Accuse de reception '.$this->trackingNumber
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.public-demand-acknowledgement-text',
            with: [
                'trackingNumber' => $this->trackingNumber,
                'subjectLabel' => $this->subjectLabel,
                'recipientName' => $this->recipientName,
                'maxProcessingHours' => $this->maxProcessingHours,
            ]
        );
    }
}
