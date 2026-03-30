<?php

namespace App\Http\Controllers\Web\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminReferentielController extends BaseAdminController
{
    public function indexDirections(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageParams((int) $actor->id_utilisateur);

        $directions = DB::table('directions')->orderBy('code')->get();
        $services = DB::table('services')->get();

        return view('admin.referentiels.directions', [
            'actor' => $actor,
            'directions' => $directions,
            'services' => $services,
        ]);
    }

    public function storeDirection(Request $request): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request): void {
            $payload = $request->validate([
                'code' => ['required', 'string', 'max:30'],
                'libelle' => ['required', 'string', 'max:255'],
            ]);

            DB::table('directions')->updateOrInsert(
                ['code' => strtoupper(trim($payload['code']))],
                [
                    'libelle' => trim($payload['libelle']),
                    'actif' => true,
                    'date_debut_validite' => now()->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }, 'params', 'CREATION_DIRECTION', "Création/Mise à jour de la direction {$request->input('code')}");
    }

    public function updateDirection(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request, $id): void {
            $payload = $request->validate([
                'libelle' => ['required', 'string', 'max:255'],
                'actif' => ['nullable', 'boolean'],
            ]);

            DB::table('directions')->where('id_direction', $id)->update([
                'libelle' => trim($payload['libelle']),
                'actif' => (bool) ($payload['actif'] ?? false),
                'updated_at' => now(),
            ]);
        }, 'params', 'MODIFICATION_DIRECTION', "Modification de la direction ID-{$id}");
    }

    public function indexServices(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageParams((int) $actor->id_utilisateur);

        $directions = DB::table('directions')->orderBy('code')->get();
        $services = DB::table('services as s')
            ->join('directions as d', 'd.id_direction', '=', 's.id_direction')
            ->select('s.*', 'd.code as direction_code', 'd.libelle as direction_libelle')
            ->orderBy('d.code')
            ->orderBy('s.code')
            ->get();

        return view('admin.referentiels.services', [
            'actor' => $actor,
            'directions' => $directions,
            'services' => $services,
        ]);
    }

    public function storeService(Request $request): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request): void {
            $payload = $request->validate([
                'id_direction' => ['required', 'integer', 'exists:directions,id_direction'],
                'code' => ['required', 'string', 'max:30'],
                'libelle' => ['required', 'string', 'max:255'],
                'email_service' => ['nullable', 'email', 'max:255'],
            ]);

            DB::table('services')->updateOrInsert(
                ['code' => strtoupper(trim($payload['code']))],
                [
                    'id_direction' => (int) $payload['id_direction'],
                    'libelle' => trim($payload['libelle']),
                    'email_service' => $payload['email_service'] ?? null,
                    'actif' => true,
                    'date_debut_validite' => now()->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }, 'params', 'CREATION_SERVICE', "Création/Mise à jour du service {$request->input('code')}");
    }

    public function updateService(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request, $id): void {
            $payload = $request->validate([
                'id_direction' => ['required', 'integer', 'exists:directions,id_direction'],
                'libelle' => ['required', 'string', 'max:255'],
                'email_service' => ['nullable', 'email', 'max:255'],
                'actif' => ['nullable', 'boolean'],
            ]);

            DB::table('services')->where('id_service', $id)->update([
                'id_direction' => (int) $payload['id_direction'],
                'libelle' => trim($payload['libelle']),
                'email_service' => $payload['email_service'] ?? null,
                'actif' => (bool) ($payload['actif'] ?? false),
                'updated_at' => now(),
            ]);
        }, 'params', 'MODIFICATION_SERVICE', "Modification du service ID-{$id}");
    }
}
