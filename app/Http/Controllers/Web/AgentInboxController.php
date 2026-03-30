<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\InteractsWithServiceWindow;
use App\Services\AccessControlService;
use App\Services\DemandWorkflowService;
use App\Services\StepAlertService;
use App\Services\WorkingHoursSlaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AgentInboxController extends Controller
{
    use InteractsWithServiceWindow;

    public function __construct(
        private readonly AccessControlService $access,
        private readonly DemandWorkflowService $workflow,
        private readonly StepAlertService $alerts,
        private readonly WorkingHoursSlaService $slaService
    ) {
    }

    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->access->assertPermission((int) $actor->id_utilisateur, 'demande.reply.send');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));

        $serviceScope = $actor->id_service ? [(int) $actor->id_service] : null;
        $this->alerts->refreshOpenDemandAlerts($serviceScope);

        $query = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->join('parametres as td', 'td.id_parametre', '=', 'd.id_type_demande')
            ->leftJoin('services as s', 's.id_service', '=', 'd.id_service_courant')
            ->leftJoin('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->where('d.id_agent_traitant', (int) $actor->id_utilisateur)
            ->whereIn('st.code', ['affectee_agent', 'reponse_prete'])
            ->select(
                'd.id_demande',
                'd.numero_suivi',
                'd.objet',
                'd.message',
                'd.id_config_sla',
                'd.date_soumission',
                'd.date_affectation_accueil',
                'd.date_affectation_agent',
                'd.alerte_agent',
                'st.code as statut_code',
                'st.libelle as statut',
                'td.libelle as type_demande',
                's.code as service_code',
                's.libelle as service',
                'u.nom as usager_nom',
                'u.prenom as usager_prenom',
                'u.email as usager_email',
                'u.telephone as usager_telephone'
            );

        if ($search !== '') {
            $pattern = '%'.$search.'%';
            $query->where(function ($q) use ($pattern) {
                $q->where('d.numero_suivi', 'like', $pattern)
                    ->orWhere('d.objet', 'like', $pattern)
                    ->orWhere('u.nom', 'like', $pattern)
                    ->orWhere('u.prenom', 'like', $pattern);
            });
        }

        $demandes = $query->orderByDesc('d.date_affectation_agent')->paginate(15)->withQueryString();
        $demandes->setCollection(
            $demandes->getCollection()->map(
                fn ($demande) => $this->withServiceWindowMeta($demande, $this->slaService)
            )
        );
        $demandIds = collect($demandes->items())
            ->pluck('id_demande')
            ->unique()
            ->values()
            ->all();

        $piecesByDemand = collect();
        if (!empty($demandIds)) {
            $piecesByDemand = DB::table('demande_piece_jointe as dpj')
                ->join('pieces_jointes as pj', 'pj.id_piece_jointe', '=', 'dpj.id_piece_jointe')
                ->whereIn('dpj.id_demande', $demandIds)
                ->orderByDesc('pj.id_piece_jointe')
                ->get([
                    'dpj.id_demande',
                    'pj.id_piece_jointe',
                    'pj.nom_fichier',
                    'pj.chemin_fichier',
                ])
                ->groupBy('id_demande');
        }

        return view('workflow.agent-inbox', [
            'actor' => $actor,
            'search' => $search,
            'demandes' => $demandes,
            'piecesByDemand' => $piecesByDemand,
            'canPilotage' => $this->access->hasPermission((int) $actor->id_utilisateur, 'dashboard.view'),
        ]);
    }

    public function envoyer(Request $request, int $id): RedirectResponse
    {
        try {
            $actor = $this->access->requireActor($request);
            $this->access->assertPermission((int) $actor->id_utilisateur, 'demande.reply.send');
            $this->access->assertDemandAccess((int) $actor->id_utilisateur, $id);

            $payload = $request->validate([
                'contenu_reponse' => ['required', 'string', 'min:5'],
                'pieces_jointes' => ['nullable', 'array', 'max:5'],
                'pieces_jointes.*' => ['nullable', 'file', 'max:4096'],
            ]);

            $draft = $this->workflow->draftResponse(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id,
                content: trim($payload['contenu_reponse']),
                typeCode: 'finale'
            );

            $files = $request->file('pieces_jointes', []);
            $this->workflow->attachFilesToResponse(
                actorId: (int) $actor->id_utilisateur,
                responseId: (int) $draft['id_reponse'],
                files: is_array($files) ? $files : []
            );

            $this->workflow->sendFinalResponse(
                actorId: (int) $actor->id_utilisateur,
                demandId: $id
            );

            return redirect()->back()->with('success', 'Reponse envoyee et demande cloturee.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
