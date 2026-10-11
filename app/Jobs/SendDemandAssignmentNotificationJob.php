<?php

namespace App\Jobs;

use App\Services\DemandWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendDemandAssignmentNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /**
     * @param array{
     *     demand_id: int,
     *     actor_id: int,
     *     email: string,
     *     recipient_name: string,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * } $mailPayload
     */
    public function __construct(public readonly array $mailPayload)
    {
    }

    public function handle(DemandWorkflowService $workflow): void
    {
        try {
            $workflow->deliverQueuedAssignmentNotification($this->mailPayload);
        } catch (Throwable $exception) {
            $workflow->markQueuedAssignmentNotificationFailed($this->mailPayload, $exception);
        }
    }
}
