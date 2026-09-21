<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PublicDemandService
{
    private const ALLOWED_ATTACHMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    private const ALLOWED_ATTACHMENT_MIMES = ['application/pdf', 'image/jpeg', 'image/png'];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?UploadedFile $file): string
    {
        $configSlaId = DB::table('config_sla')->where('actif', true)->value('id_config_sla');
        if (! $configSlaId) {
            throw ValidationException::withMessages([
                'type_demande_code' => 'Configuration des délais absente.',
            ]);
        }

        $typeId = DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('code', 'reclamation')
            ->where('actif', true)
            ->value('id_parametre');
        if (! $typeId) {
            throw ValidationException::withMessages([
                'objet' => 'Type de demande invalide.',
            ]);
        }

        $statusId = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'nouvelle')
            ->where('actif', true)
            ->value('id_parametre');
        if (! $statusId) {
            throw ValidationException::withMessages([
                'objet' => 'Statut de demande invalide.',
            ]);
        }

        $attachment = $this->storeAttachment($file);

        try {
            return DB::transaction(function () use ($payload, $attachment, $typeId, $statusId, $configSlaId): string {
                $now = now();
                $tracking = $this->nextTrackingNumber();
                $usagerId = $this->createUsagerSnapshot($payload, $now);

                $demandId = DB::table('demandes')->insertGetId([
                    'numero_suivi' => $tracking,
                    'id_usager' => $usagerId,
                    'id_type_demande' => $typeId,
                    'id_statut' => $statusId,
                    'id_config_sla' => $configSlaId,
                    'objet' => $payload['objet'],
                    'categorie' => ! empty($payload['categorie']) ? trim((string) $payload['categorie']) : null,
                    'message' => trim((string) $payload['message']),
                    'date_soumission' => $now,
                    'alerte_accueil' => 'vert',
                    'delai_alerte' => 'dans_les_delais',
                    'created_at' => $now,
                    'updated_at' => $now,
                ], 'id_demande');

                if (! empty($payload['categorie'])) {
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

                if ($attachment !== null) {
                    $pieceId = DB::table('pieces_jointes')->insertGetId([
                        'nom_fichier' => $attachment['name'],
                        'chemin_fichier' => $attachment['path'],
                        'taille_octets' => $attachment['size'],
                        'type_mime' => $attachment['mime'],
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
        } catch (Throwable $exception) {
            if ($attachment !== null) {
                $this->deleteAttachmentAfterFailure($attachment['path']);
            }

            throw $exception;
        }
    }

    /**
     * @return array{path: string, name: string, size: int, mime: string}|null
     */
    private function storeAttachment(?UploadedFile $file): ?array
    {
        if ($file === null) {
            return null;
        }

        $this->ensureAllowedAttachment($file);

        try {
            $path = $file->store('pieces_jointes', 'local');
        } catch (Throwable $exception) {
            Log::error('Echec stockage piece jointe demande publique', [
                'message' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'piece_jointe' => "La pièce jointe n'a pas pu être enregistrée. Veuillez réessayer.",
            ]);
        }

        if (! is_string($path) || $path === '') {
            Log::error('Echec stockage piece jointe demande publique', [
                'message' => 'Le disque local a refuse l ecriture du fichier.',
            ]);

            throw ValidationException::withMessages([
                'piece_jointe' => "La pièce jointe n'a pas pu être enregistrée. Veuillez réessayer.",
            ]);
        }

        return [
            'path' => $path,
            'name' => $this->safeOriginalFilename($file),
            'size' => (int) $file->getSize(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
        ];
    }

    private function deleteAttachmentAfterFailure(string $path): void
    {
        try {
            Storage::disk('local')->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Echec nettoyage piece jointe apres annulation demande publique', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createUsagerSnapshot(array $payload, mixed $now): int
    {
        $email = $payload['email'] ?? null;

        return (int) DB::table('usagers')->insertGetId([
            'nom' => trim((string) $payload['nom']),
            'prenom' => trim((string) ($payload['prenom'] ?? '')),
            'email' => $email ? trim((string) $email) : null,
            'statut_usager' => trim((string) ($payload['statut_usager'] ?? '')),
            'pays' => trim((string) ($payload['pays'] ?? '')),
            'etablissement' => filled($payload['etablissement'] ?? null) ? trim((string) $payload['etablissement']) : null,
            'consentement_rgpd' => isset($payload['consentement']) ? (bool) $payload['consentement'] : false,
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_usager');
    }

    private function nextTrackingNumber(): string
    {
        $year = now()->format('Y');

        if (DB::getDriverName() === 'pgsql') {
            $next = DB::selectOne("SELECT nextval('demandes_numero_seq') AS val")->val;

            return sprintf('ANBG-%s-%03d', $year, $next);
        }

        $counter = DB::table('demandes_numero_compteurs')
            ->where('annee', (int) $year)
            ->when(DB::getDriverName() !== 'sqlite', fn ($query) => $query->lockForUpdate())
            ->first();

        if (! $counter) {
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

    private function safeOriginalFilename(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $baseName = Str::of($baseName)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '_')
            ->trim('._-')
            ->limit(80, '')
            ->value();

        if ($baseName === '') {
            $baseName = 'piece_jointe';
        }

        return $extension !== '' ? "{$baseName}.{$extension}" : $baseName;
    }

    private function ensureAllowedAttachment(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) ($file->getMimeType() ?: ''));

        if (! in_array($extension, self::ALLOWED_ATTACHMENT_EXTENSIONS, true)
            || ! in_array($mime, self::ALLOWED_ATTACHMENT_MIMES, true)) {
            throw ValidationException::withMessages([
                'piece_jointe' => 'Format de fichier non autorisé. Utilisez PDF, JPG ou PNG.',
            ]);
        }
    }
}
