<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Demande;
use App\Services\AccessControlService;
use App\Services\DemandWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class DemandDeliveryController extends Controller
{
    public function __construct(
        private readonly AccessControlService $access,
        private readonly DemandWorkflowService $workflow
    ) {
    }

    public function retry(Request $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $demand = Demande::query()->find($id);

            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            Gate::forUser($actor)->authorize('reply', $demand);

            if (empty($demand->date_echec_envoi_usager)) {
                throw new RuntimeException("Aucun echec d'envoi n'est à relancer pour cette demande.");
            }

            $this->workflow->sendFinalResponse(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id
            );

            return redirect()->back()->with('success', "Relance d'envoi mise en file. La demande sera cloturée après confirmation d'envoi.");
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
