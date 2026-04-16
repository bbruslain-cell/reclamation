<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicDemandController extends Controller
{
    public function create(): View
    {
        $types = DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('actif', true)
            ->orderBy('ordre_affichage')
            ->get(['code', 'libelle']);

        return view('public.create-demand', ['types' => $types]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'statut_usager' => ['required', 'string', 'max:100'],
            'pays' => ['required', 'string', 'max:120'],
            'etablissement' => [
                Rule::requiredIf(fn () => in_array($this->normalizeUsagerStatus((string) $request->input('statut_usager')), ['eleve', 'etudiant'], true)),
                'nullable',
                'string',
                'max:255',
            ],
            'categorie' => ['nullable', 'string', 'max:255'],
            'objet' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
            'piece_jointe' => ['nullable', 'file', 'max:2048', 'mimes:pdf,jpg,jpeg,png'],
            'consentement' => ['accepted'],
        ]);

        $configSlaId = DB::table('config_sla')->where('actif', true)->value('id_config_sla');
        if (!$configSlaId) {
            throw ValidationException::withMessages([
                'type_demande_code' => 'Configuration SLA absente.',
            ]);
        }

        $typeId = DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('code', 'reclamation')
            ->where('actif', true)
            ->value('id_parametre');
        if (!$typeId) {
            throw ValidationException::withMessages([
                'objet' => 'Type de demande invalide.',
            ]);
        }

        $statusId = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'nouvelle')
            ->value('id_parametre');

        $usagerId = $this->createUsagerSnapshot($payload);

        $tracking = DB::transaction(function () use ($request, $payload, $usagerId, $typeId, $statusId, $configSlaId): string {
            $now = now();
            $tracking = $this->nextTrackingNumber();

            $demandId = DB::table('demandes')->insertGetId([
                'numero_suivi' => $tracking,
                'id_usager' => $usagerId,
                'id_type_demande' => $typeId,
                'id_statut' => $statusId,
                'id_config_sla' => $configSlaId,
                'objet' => trim($payload['objet']),
                'categorie' => !empty($payload['categorie']) ? trim($payload['categorie']) : null,
                'message' => trim($payload['message']),
                'date_soumission' => $now,
                'alerte_accueil' => 'vert',
                'delai_alerte' => 'dans_les_delais',
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_demande');

            if (!empty($payload['categorie'])) {
                DB::table('historique_actions')->insert([
                    'id_demande' => $demandId,
                    'id_utilisateur' => null,
                    'type_action' => 'categorie_usager',
                    'ancien_statut_id' => null,
                    'nouveau_statut_id' => $statusId,
                    'id_service_associe' => null,
                    'date_action' => $now,
                    'commentaire' => 'Categorie choisie: '.$payload['categorie'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('historique_actions')->insert([
                'id_demande' => $demandId,
                'id_utilisateur' => null,
                'type_action' => 'soumission_usager',
                'ancien_statut_id' => null,
                'nouveau_statut_id' => $statusId,
                'id_service_associe' => null,
                'date_action' => $now,
                'commentaire' => 'Soumission publique',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $file = $request->file('piece_jointe');
            if ($file) {
                $this->ensureAllowedAttachment($file);

                $path = $file->store('pieces_jointes', 'local');
                $pieceId = DB::table('pieces_jointes')->insertGetId([
                    'nom_fichier' => $file->getClientOriginalName(),
                    'chemin_fichier' => $path,
                    'taille_octets' => $file->getSize(),
                    'type_mime' => $file->getMimeType() ?: 'application/octet-stream',
                    'id_uploadeur' => null,
                    'source' => 'usager',
                    'date_upload' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], 'id_piece_jointe');

                DB::table('demande_piece_jointe')->insert([
                    'id_demande' => $demandId,
                    'id_piece_jointe' => $pieceId,
                ]);
            }

            return $tracking;
        });

        return redirect('/reclamations/nouvelle')
            ->with('success', "Votre demande est enregistree avec succes. Numero de suivi : {$tracking}");
    }

    private function createUsagerSnapshot(array $payload): int
    {
        $email = $payload['email'] ?? null;
        $base = [
            'nom' => trim($payload['nom']),
            'prenom' => trim((string) ($payload['prenom'] ?? '')),
            'statut_usager' => trim((string) ($payload['statut_usager'] ?? '')),
            'pays' => trim((string) ($payload['pays'] ?? '')),
            'etablissement' => filled($payload['etablissement'] ?? null) ? trim((string) $payload['etablissement']) : null,
            'consentement_rgpd' => isset($payload['consentement']) ? (bool) $payload['consentement'] : false,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        return (int) DB::table('usagers')->insertGetId(array_merge($base, [
            'email' => $email ? trim($email) : null,
        ]), 'id_usager');
    }

    private function nextTrackingNumber(): string
    {
        $year = now()->format('Y');

        if (DB::getDriverName() === 'pgsql') {
            $next = DB::selectOne("SELECT nextval('demandes_numero_seq') AS val")->val;

            return sprintf('ANBG-%s-%03d', $year, $next);
        }

        $counterQuery = DB::table('demandes_numero_compteurs')->where('annee', (int) $year);
        if (DB::getDriverName() !== 'sqlite') {
            $counterQuery->lockForUpdate();
        }

        $counter = $counterQuery->first();

        if (!$counter) {
            DB::table('demandes_numero_compteurs')->insert([
                'annee' => (int) $year,
                'valeur' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('demandes_numero_compteurs')
                ->where('annee', (int) $year)
                ->when(DB::getDriverName() !== 'sqlite', fn ($query) => $query->lockForUpdate())
                ->first();
        }

        $next = ((int) ($counter->valeur ?? 0)) + 1;

        DB::table('demandes_numero_compteurs')
            ->where('annee', (int) $year)
            ->update([
                'valeur' => $next,
                'updated_at' => now(),
            ]);

        return sprintf('ANBG-%s-%03d', $year, $next);
    }

    private function normalizeUsagerStatus(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->trim()
            ->value();
    }

    private function ensureAllowedAttachment(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) ($file->getMimeType() ?: ''));

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

        if (!in_array($extension, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'piece_jointe' => 'Format de fichier non autorise. Utilisez PDF, JPG ou PNG.',
            ]);
        }
    }
}
