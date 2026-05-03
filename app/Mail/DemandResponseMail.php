<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DemandResponseMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array{name: string, path: string, mime: string|null}> $attachments
     */
    public function __construct(
        public readonly string $trackingNumber,
        public readonly string $subjectLabel,
        public readonly string $responseContent,
        public readonly ?string $recipientName = null,
        public readonly array $responseAttachments = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réponse à votre demande '.$this->trackingNumber
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demand-response',
            with: [
                'trackingNumber' => $this->trackingNumber,
                'subjectLabel' => $this->subjectLabel,
                'responseContent' => $this->responseContent,
                'recipientName' => $this->recipientName,
                'attachmentCount' => count($this->responseAttachments),
            ]
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->responseAttachments)
            ->map(function (array $attachment): ?Attachment {
                $disk = $this->resolveAttachmentDisk((string) ($attachment['path'] ?? ''));
                if ($disk === null) {
                    return null;
                }

                return Attachment::fromStorageDisk($disk, $attachment['path'])
                    ->as($attachment['name'])
                    ->withMime($attachment['mime'] ?: 'application/octet-stream');
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveAttachmentDisk(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }
}
