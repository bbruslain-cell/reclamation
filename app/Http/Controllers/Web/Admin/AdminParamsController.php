<?php

namespace App\Http\Controllers\Web\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminParamsController extends BaseAdminController
{
    private const NON_PARAMETRABLE_FAMILIES = [
        'format_export',
        'seuil_alerte',
        'statut_demande',
        'statut_notif',
        'type_demande',
        'type_notif',
        'type_reponse',
    ];

    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageParams($actor);

        $configSla = DB::table('config_sla')->where('actif', true)->first();

        $parametres = DB::table('parametres')
            ->whereNotIn('famille', self::NON_PARAMETRABLE_FAMILIES)
            ->orderBy('famille')
            ->orderBy('ordre_affichage')
            ->get();

        $joursFeries = $configSla
            ? DB::table('sla_jours_feries')
                ->where('id_config_sla', $configSla->id_config_sla)
                ->orderBy('date_ferie')
                ->get()
            : collect();

        $joursOuvres = $configSla
            ? DB::table('sla_jours_ouvres')
                ->where('id_config_sla', $configSla->id_config_sla)
                ->where('actif', true)
                ->orderBy('jour_semaine_iso')
                ->get()
            : collect();

        $configurationsSla = DB::table('config_sla')->orderBy('created_at', 'desc')->get();

        return view('admin.parametres.index', [
            'actor' => $actor,
            'parametres' => $parametres,
            'slaConfig' => $configSla,
            'joursFeries' => $joursFeries,
            'joursOuvres' => $joursOuvres,
            'configurationsSla' => $configurationsSla,
        ]);
    }

    public function storeParametre(Request $request): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request): void {
            $payload = $request->validate([
                'famille' => ['required', 'string', 'max:80'],
                'code' => ['required', 'string', 'max:80'],
                'libelle' => ['required', 'string', 'max:255'],
                'ordre_affichage' => ['nullable', 'integer', 'min:1', 'max:999'],
            ]);

            $family = trim($payload['famille']);
            if (in_array($family, self::NON_PARAMETRABLE_FAMILIES, true)) {
                throw ValidationException::withMessages([
                    'famille' => "Cette famille n'est pas paramétrable depuis l'espace admin.",
                ]);
            }

            DB::table('parametres')->updateOrInsert(
                ['famille' => $family, 'code' => trim($payload['code'])],
                [
                    'libelle' => trim($payload['libelle']),
                    'ordre_affichage' => (int) ($payload['ordre_affichage'] ?? 1),
                    'actif' => true,
                    'date_debut_validite' => now()->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }, 'params', 'CREATION_PARAMETRE', "Création ou mise à jour du paramètre {$request->input('famille')}.{$request->input('code')}");
    }

    public function updateParametre(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request, $id): void {
            $parametre = DB::table('parametres')->where('id_parametre', $id)->first();
            if (!$parametre) {
                throw ValidationException::withMessages(['libelle' => 'Paramètre introuvable.']);
            }

            if (in_array($parametre->famille, self::NON_PARAMETRABLE_FAMILIES, true)) {
                throw ValidationException::withMessages([
                    'libelle' => "Cette famille n'est pas paramétrable depuis l'espace admin.",
                ]);
            }

            $payload = $request->validate([
                'libelle' => ['required', 'string', 'max:255'],
                'ordre_affichage' => ['nullable', 'integer', 'min:1', 'max:999'],
                'actif' => ['nullable', 'boolean'],
            ]);

            DB::table('parametres')->where('id_parametre', $id)->update([
                'libelle' => trim($payload['libelle']),
                'ordre_affichage' => (int) ($payload['ordre_affichage'] ?? 1),
                'actif' => (bool) ($payload['actif'] ?? false),
                'updated_at' => now(),
            ]);
        }, 'params', 'MODIFICATION_PARAMETRE', "Mise à jour du paramètre ID-{$id} (nouvel ordre/statut)");
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request): void {
            $payload = $request->validate([
                'date_ferie' => ['required', 'date'],
                'date_fin' => ['nullable', 'date', 'after_or_equal:date_ferie'],
                'libelle' => ['required', 'string', 'max:100'],
            ]);

            $configId = DB::table('config_sla')->where('actif', true)->value('id_config_sla');
            if (!$configId) {
                throw ValidationException::withMessages(['date_ferie' => 'Aucune configuration de délai active.']);
            }

            DB::table('sla_jours_feries')->updateOrInsert(
                ['id_config_sla' => $configId, 'date_ferie' => $payload['date_ferie']],
                [
                    'date_fin' => $payload['date_fin'] ?? null,
                    'libelle' => trim($payload['libelle']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }, 'params', 'AJOUT_JOUR_FERIE', "Ajout d'un jour férié : {$request->input('libelle')}");
    }

    public function updateHoliday(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request, $id): void {
            $payload = $request->validate([
                'date_ferie' => ['required', 'date'],
                'date_fin' => ['nullable', 'date', 'after_or_equal:date_ferie'],
                'libelle' => ['required', 'string', 'max:100'],
            ]);

            $row = DB::table('sla_jours_feries')->where('id_sla_jour_ferie', $id)->first();
            if (!$row) {
                throw ValidationException::withMessages(['date_ferie' => 'Jour férié introuvable.']);
            }

            $exists = DB::table('sla_jours_feries')
                ->where('id_config_sla', $row->id_config_sla)
                ->where('date_ferie', $payload['date_ferie'])
                ->where('id_sla_jour_ferie', '!=', $id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['date_ferie' => 'Cette date existe déjà pour la configuration active.']);
            }

            DB::table('sla_jours_feries')
                ->where('id_sla_jour_ferie', $id)
                ->update([
                    'date_ferie' => $payload['date_ferie'],
                    'date_fin' => $payload['date_fin'] ?? null,
                    'libelle' => trim($payload['libelle']),
                    'updated_at' => now(),
                ]);
        }, 'params', 'MODIFICATION_JOUR_FERIE', "Mise à jour du jour férié ID-{$id}");
    }

    public function deleteHoliday(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($id): void {
            DB::table('sla_jours_feries')->where('id_sla_jour_ferie', $id)->delete();
        }, 'params', 'SUPPRESSION_JOUR_FERIE', "Suppression du jour férié ID-{$id}");
    }
}
