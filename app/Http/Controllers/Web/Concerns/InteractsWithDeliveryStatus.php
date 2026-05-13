<?php

namespace App\Http\Controllers\Web\Concerns;

trait InteractsWithDeliveryStatus
{
    protected function withDeliveryStatusMeta(object $demand): object
    {
        $hasPendingDelivery = !empty($demand->date_demande_envoi_usager) && empty($demand->date_envoi_usager);
        $hasFailedDelivery = !$hasPendingDelivery
            && empty($demand->date_envoi_usager)
            && !empty($demand->date_echec_envoi_usager);
        $isResponseReady = (string) ($demand->statut_code ?? '') === 'reponse_prete';

        $demand->delivery_state = match (true) {
            $hasPendingDelivery => 'pending',
            !empty($demand->date_envoi_usager) => 'sent',
            $hasFailedDelivery => 'failed',
            $isResponseReady => 'ready',
            default => 'idle',
        };

        return $demand;
    }
}
