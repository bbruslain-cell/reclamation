<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Closure;
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
    private const USAGER_STATUSES = [
        'Élève',
        'Étudiant',
        'Parent / Tuteur',
        'Enseignant',
        'Professionnel',
        'Autre',
    ];

    private const USAGER_STATUS_ALIASES = [
        'Eleve',
        'Etudiant',
    ];

    private const CATEGORIES_BY_TYPE = [
        'reclamation' => [
            "Demande de modification d'attestation d'attribution de bourse ou maintien",
            'Réclamation du paiement des frais de scolarité',
            'Réclamation sur les RIB non validés sur eBourse',
            'Recours après déliberation de la CT',
            'Réclamation diverses',
        ],
        'autre' => ['Autre'],
    ];

    private const ACCEPTED_EMAIL_TLDS = [
        'com', 'net', 'org', 'edu', 'gov', 'info', 'biz', 'pro', 'name',
        'ga', 'fr', 'gq', 'cm', 'cg', 'cd', 'cf', 'td', 'sn', 'ci', 'bj', 'tg', 'bf', 'ml', 'ne',
        'ng', 'gh', 'ke', 'rw', 'ug', 'tz', 'za', 'ma', 'tn', 'dz', 'eg', 'ao', 'mz', 'mg', 'mu',
        'us', 'ca', 'uk', 'de', 'es', 'it', 'pt', 'be', 'ch', 'nl', 'lu', 'ie', 'se', 'no', 'dk',
        'fi', 'pl', 'cz', 'at', 'gr', 'ro', 'bg', 'hu', 'sk', 'si', 'hr', 'lt', 'lv', 'ee',
        'br', 'mx', 'ar', 'cl', 'co', 'pe', 'uy', 'py', 'ec', 'bo',
        'au', 'nz', 'jp', 'kr', 'cn', 'in', 'sg', 'my', 'id', 'ph', 'th', 'vn', 'hk', 'tw',
        'ae', 'sa', 'qa', 'il', 'tr',
        'io', 'ai', 'app', 'dev', 'cloud', 'online', 'site', 'tech', 'store', 'shop', 'me', 'tv',
    ];

    public function create(): View
    {
        $types = DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('actif', true)
            ->orderBy('ordre_affichage')
            ->get(['code', 'libelle']);

        return view('public.create-demand', [
            'types' => $types,
            'usagerStatuses' => self::USAGER_STATUSES,
            'categoriesByType' => self::CATEGORIES_BY_TYPE,
            'acceptedEmailTldPattern' => $this->acceptedEmailTldPattern(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'nom' => ['required', 'string', 'max:255', $this->personNameRule(), $this->safePublicTextRule()],
            'prenom' => ['required', 'string', 'max:255', $this->personNameRule(), $this->safePublicTextRule()],
            'email' => ['required', 'string', 'max:255', 'email:rfc', $this->emailDomainRule()],
            'statut_usager' => ['required', 'string', Rule::in($this->allowedUsagerStatuses())],
            'pays' => ['required', 'string', 'max:120', $this->personNameRule(), $this->safePublicTextRule()],
            'etablissement' => [
                Rule::requiredIf(fn () => in_array($this->normalizeUsagerStatus((string) $request->input('statut_usager')), ['eleve', 'etudiant'], true)),
                'nullable',
                'string',
                'max:255',
                $this->organizationNameRule(),
                $this->safePublicTextRule(),
            ],
            'categorie' => ['nullable', 'string', 'max:255', Rule::in($this->allowedCategories()), $this->safePublicTextRule()],
            'objet' => ['nullable', 'string', 'max:255', $this->safePublicTextRule()],
            'message' => ['required', 'string', 'min:10', 'max:2000', $this->safePublicTextRule()],
            'piece_jointe' => ['nullable', 'file', 'max:3584', 'mimes:pdf,jpg,jpeg,png'],
            'consentement' => ['accepted'],
        ], [
            'statut_usager.in' => 'Statut usager invalide.',
            'nom.regex' => 'Le nom contient des caracteres non autorises.',
            'prenom.regex' => 'Le prenom contient des caracteres non autorises.',
            'pays.regex' => 'Le pays contient des caracteres non autorises.',
            'etablissement.regex' => "L'etablissement contient des caracteres non autorises.",
            'categorie.in' => 'Categorie invalide.',
            'email.email' => 'Veuillez saisir une adresse email valide avec un domaine complet, par exemple nom@example.com.',
            'email.regex' => 'Veuillez saisir une adresse email valide avec un domaine complet, par exemple nom@example.com.',
            'message.min' => 'Le message doit contenir au moins 10 caractères.',
            'message.max' => 'Le message ne doit pas dépasser 2000 caractères.',
        ]);

        $payload['objet'] = filled($payload['categorie'] ?? null)
            ? trim((string) $payload['categorie'])
            : trim((string) ($payload['objet'] ?? ''));

        if ($payload['objet'] === '') {
            throw ValidationException::withMessages([
                'categorie' => 'La catégorie est requise.',
            ]);
        }

        $configSlaId = DB::table('config_sla')->where('actif', true)->value('id_config_sla');
        if (!$configSlaId) {
            throw ValidationException::withMessages([
                'type_demande_code' => 'Configuration des délais absente.',
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
                'objet' => $payload['objet'],
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
                    'nom_fichier' => $this->safeOriginalFilename($file),
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
            ->with('success', "Votre demande est enregistrée avec succès. Numero de suivi : {$tracking}");
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

    /**
     * @return list<string>
     */
    private function allowedUsagerStatuses(): array
    {
        return array_values(array_unique(array_merge(self::USAGER_STATUSES, self::USAGER_STATUS_ALIASES)));
    }

    /**
     * @return list<string>
     */
    private function allowedCategories(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::CATEGORIES_BY_TYPE))));
    }

    private function personNameRule(): string
    {
        return 'regex:/^[\p{L}\p{M}][\p{L}\p{M}\s.\'\x{2019}-]*$/u';
    }

    private function organizationNameRule(): string
    {
        return 'regex:/^[\p{L}\p{M}0-9][\p{L}\p{M}0-9\s.\'\x{2019},()\/-]*$/u';
    }

    private function safePublicTextRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $text = (string) $value;

            if (!mb_check_encoding($text, 'UTF-8')) {
                $fail($this->unsafePublicTextMessage($attribute));
                return;
            }

            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $text) === 1) {
                $fail($this->unsafePublicTextMessage($attribute));
                return;
            }

            if (preg_match('/[<>]|&(?:lt|gt);|javascript\s*:|vbscript\s*:|data\s*:\s*text\/html|on[a-z]+\s*=/iu', $text) === 1) {
                $fail($this->unsafePublicTextMessage($attribute));
                return;
            }

            if (preg_match('/--|\/\*|\*\/|\b(?:union\s+select|select\s+.+\s+from|insert\s+into|update\s+\w+\s+set|delete\s+from|drop\s+(?:table|database)|alter\s+table|truncate\s+table|(?:or|and)\s+\d+\s*=\s*\d+)\b/iu', $text) === 1) {
                $fail($this->unsafePublicTextMessage($attribute));
            }
        };
    }

    private function unsafePublicTextMessage(string $attribute): string
    {
        $label = str_replace('_', ' ', $attribute);

        return "Le champ {$label} contient des caracteres ou expressions non autorises.";
    }

    private function emailDomainRule(): string
    {
        return "regex:/^[A-Z0-9._%+\\-']+@(?:[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?\\.)+(?:".$this->acceptedEmailTldPattern().')$/i';
    }

    private function acceptedEmailTldPattern(): string
    {
        return implode('|', self::ACCEPTED_EMAIL_TLDS);
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

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

        if (!in_array($extension, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'piece_jointe' => 'Format de fichier non autorisé. Utilisez PDF, JPG ou PNG.',
            ]);
        }
    }
}
