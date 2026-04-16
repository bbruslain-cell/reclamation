<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\AccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AgentPortalController extends Controller
{
    public function __construct(private readonly AccessControlService $access)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $actor = $this->access->requireActor($request);
        $userId = (int) $actor->id_utilisateur;
        $roles = $this->access->roleCodes($userId);
        $spaces = $this->availableSpaces($actor, $roles);
        $defaultSpace = $this->resolveDefaultSpace($spaces);

        if (count($roles) > 1 && count($spaces) > 1) {
            return view('workspace-select', [
                'actor' => $actor,
                'roles' => $roles,
                'spaces' => $spaces,
                'defaultSpace' => $defaultSpace,
            ]);
        }

        if ($defaultSpace !== null) {
            return redirect($defaultSpace['href']);
        }

        return redirect('/login');
    }

    private function availableSpaces(Utilisateur $actor, array $roles): array
    {
        $spaces = [];

        if (Gate::forUser($actor)->allows('admin.access')) {
            $spaces['admin'] = [
                'code' => 'admin',
                'title' => 'Administration',
                'description' => 'Gerer les utilisateurs, les roles, les referentiels et les parametres.',
                'href' => '/admin',
                'badge' => 'Configuration',
            ];
        }

        if (in_array('accueil', $roles, true)) {
            $spaces['accueil'] = [
                'code' => 'accueil',
                'title' => 'Accueil',
                'description' => 'Recevoir, qualifier, affecter et traiter directement les demandes simples.',
                'href' => '/accueil/inbox',
                'badge' => 'UCAS',
            ];
        }

        if (in_array('chef_service', $roles, true)) {
            $spaces['chef'] = [
                'code' => 'chef',
                'title' => 'Chef de service',
                'description' => 'Piloter le traitement du service, affecter les demandes aux  agents et suivre le delai partager.',
                'href' => '/chef/inbox',
                'badge' => 'Operationnel',
            ];
        }

        if (in_array('chef_direction', $roles, true)) {
            $spaces['chef_direction'] = [
                'code' => 'chef_direction',
                'title' => 'Chef de direction',
                'description' => 'Superviser les transactions de tous les services de la direction.',
                'href' => '/chef-direction/inbox',
                'badge' => 'Supervision',
            ];
        }

        if (in_array('agent', $roles, true)) {
            $spaces['agent'] = [
                'code' => 'agent',
                'title' => 'Agent',
                'description' => 'Traiter les demandes qui vous sont affectées et envoyer la reponse finale.',
                'href' => '/agent/inbox',
                'badge' => 'Traitement',
            ];
        }

        if (array_intersect($roles, ['ciq', 'dg', 'lecture_seule']) !== []) {
            $spaces['pilotage'] = [
                'code' => 'pilotage',
                'title' => 'Pilotage',
                'description' => 'Consulter les indicateurs, la supervision globale et les vues synthétiques.',
                'href' => '/pilotage',
                'badge' => 'Tableaux de bord',
            ];
        }

        return array_values($spaces);
    }

    private function resolveDefaultSpace(array $spaces): ?array
    {
        $priority = ['admin', 'accueil', 'chef', 'chef_direction', 'agent', 'pilotage'];

        foreach ($priority as $code) {
            foreach ($spaces as $space) {
                if (($space['code'] ?? null) === $code) {
                    return $space;
                }
            }
        }

        return null;
    }
}
