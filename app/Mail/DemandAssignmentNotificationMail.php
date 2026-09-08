<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandAssignmentNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $trackingNumber,
        public readonly string $subjectLabel,
        public readonly string $eventCode,
        public readonly string $mailSubject,
        public readonly string $recipientName,
        public readonly string $introLine,
        public readonly string $instructionLine,
        public readonly string $actorName,
        public readonly string $loginUrl,
        public readonly ?string $serviceLabel = null,
        public readonly ?string $comment = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.demand-assignment-notification-text',
            with: [
                'trackingNumber' => $this->trackingNumber,
                'subjectLabel' => $this->subjectLabel,
                'eventCode' => $this->eventCode,
                'recipientName' => $this->recipientName,
                'introLine' => $this->introLine,
                'instructionLine' => $this->instructionLine,
                'actorName' => $this->actorName,
                'serviceLabel' => $this->serviceLabel,
                'comment' => $this->comment,
                'loginUrl' => $this->loginUrl,
            ]
        );
    }
}
