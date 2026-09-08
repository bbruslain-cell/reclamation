<?php

namespace App\Jobs;

use App\Services\DemandWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendDemandResponseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param array{
     *     email: string,
     *     recipient_name: string|null,
     *     tracking_number: string,
     *     subject_label: string,
     *     response_content: string,
     *     attachments: array<int, array{name: string, path: string, mime: string|null}>
     * } $mailPayload
     */
    public function __construct(
        public readonly int $actorId,
        public readonly int $demandId,
        public readonly int $responseId,
        public readonly array $mailPayload
    ) {
    }

    public function handle(DemandWorkflowService $workflow): void
    {
        $workflow->deliverQueuedFinalResponse(
            actorId: $this->actorId,
            demandId: $this->demandId,
            responseId: $this->responseId,
            mailPayload: $this->mailPayload
        );
    }

    public function failed(Throwable $exception): void
    {
        app(DemandWorkflowService::class)->markQueuedFinalResponseFailed(
            actorId: $this->actorId,
            demandId: $this->demandId,
            responseId: $this->responseId,
            exception: $exception
        );
    }
}
