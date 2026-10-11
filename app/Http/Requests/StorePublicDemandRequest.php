<?php

namespace App\Http\Requests;

use App\Rules\AcceptedEmailDomain;
use App\Rules\SafePublicText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePublicDemandRequest extends FormRequest
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

    private const CATEGORY_ALIASES = [
        'Paiement bourse',
        'Attestation de bourse',
        'RIB',
        'Recours CT',
        'Reclamation diverses',
    ];

    private const COUNTRIES = [
        'FRANCE',
        'GABON',
        'MAROC',
        'ÉTATS-UNIS',
        'SÉNÉGAL',
        'CHINE',
        'FÉDÉRATION DE RUSSIE',
        'TUNISIE',
        'BRÉSIL',
        'CANADA',
        'GHANA',
        'AFRIQUE DU SUD',
        'ALLEMAGNE',
        'ROYAUME-UNI',
        'TURQUIE',
        'CAMEROUN',
        'BELGIQUE',
        'TOGO',
        "CÔTE D'IVOIRE",
        'ITALIE',
        'Autre',
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

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $country = $this->input('pays');

        if (is_string($country)) {
            $this->merge([
                'pays' => self::canonicalCountry($country),
            ]);
        }

        $establishment = $this->input('etablissement');

        if (is_string($establishment)) {
            $this->merge([
                'etablissement' => self::canonicalEstablishment($establishment),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255', self::personNameRule(), new SafePublicText()],
            'prenom' => ['required', 'string', 'max:255', self::personNameRule(), new SafePublicText()],
            'email' => ['required', 'string', 'max:255', 'email:rfc', new AcceptedEmailDomain(self::ACCEPTED_EMAIL_TLDS)],
            'statut_usager' => ['required', 'string', Rule::in(self::allowedUsagerStatuses())],
            'pays' => ['required', 'string', 'max:120', Rule::in(self::COUNTRIES), new SafePublicText()],
            'etablissement' => [
                Rule::requiredIf(fn () => in_array(self::normalizeUsagerStatus((string) $this->input('statut_usager')), ['eleve', 'etudiant'], true)),
                'nullable',
                'string',
                'max:255',
                self::organizationNameRule(),
                new SafePublicText(),
            ],
            'categorie' => ['nullable', 'string', 'max:255', Rule::in(self::allowedCategories()), new SafePublicText()],
            'objet' => ['nullable', 'string', 'max:255', new SafePublicText()],
            'message' => ['required', 'string', 'min:10', 'max:2000', new SafePublicText()],
            'piece_jointe' => ['nullable', 'file', 'max:3584', 'mimetypes:application/pdf,image/jpeg,image/png'],
            'consentement' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statut_usager.in' => 'Statut usager invalide.',
            'nom.regex' => 'Le nom contient des caractères non autorisés.',
            'prenom.regex' => 'Le prénom contient des caractères non autorisés.',
            'pays.regex' => 'Le pays contient des caractères non autorisés.',
            'pays.in' => 'Pays invalide. Sélectionnez un pays dans la liste ou Autre.',
            'etablissement.regex' => "L'établissement contient des caractères non autorisés.",
            'categorie.in' => 'Catégorie invalide.',
            'email.email' => 'Veuillez saisir une adresse email valide avec un domaine complet, par exemple nom@example.com.',
            'message.min' => 'Le message doit contenir au moins 10 caractères.',
            'message.max' => 'Le message ne doit pas dépasser 2000 caractères.',
            'piece_jointe.mimetypes' => 'Format de fichier non autorisé. Utilisez PDF, JPG ou PNG.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $category = trim((string) $this->input('categorie', ''));
            $object = trim((string) $this->input('objet', ''));
            $establishment = trim((string) $this->input('etablissement', ''));

            if ($category === '' && $object === '') {
                $validator->errors()->add('categorie', 'La catégorie est requise.');
            }
            if ($establishment !== '' && ! self::isAllowedEstablishment($establishment)) {
                $validator->errors()->add('etablissement', "Etablissement invalide. Selectionnez un etablissement dans la liste ou Autre.");
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $payload = $this->validated();
        $category = trim((string) ($payload['categorie'] ?? ''));
        $object = trim((string) ($payload['objet'] ?? ''));

        $payload['objet'] = $object !== '' ? $object : $category;

        return $payload;
    }

    /**
     * @return list<string>
     */
    public static function usagerStatuses(): array
    {
        return self::USAGER_STATUSES;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function categoriesByType(): array
    {
        return self::CATEGORIES_BY_TYPE;
    }

    /**
     * @return list<string>
     */
    public static function countries(): array
    {
        return self::COUNTRIES;
    }

    public static function acceptedEmailTldPattern(): string
    {
        return implode('|', self::ACCEPTED_EMAIL_TLDS);
    }

    private static function canonicalCountry(string $value): string
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        $normalized = self::normalizeReferenceKey($trimmed);

        foreach (self::COUNTRIES as $country) {
            if (self::normalizeReferenceKey($country) === $normalized) {
                return $country;
            }
        }

        return $trimmed;
    }

    private static function canonicalEstablishment(string $value): string
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        $normalized = self::normalizeReferenceKey($trimmed);

        if ($normalized === 'autre') {
            return 'Autre';
        }

        if ($normalized === '' || ! Schema::hasTable('etablissements')) {
            return $trimmed;
        }

        $match = DB::table('etablissements')
            ->where('actif', true)
            ->where('nom_normalise', $normalized)
            ->value('nom');

        return is_string($match) ? $match : $trimmed;
    }

    private static function isAllowedEstablishment(string $value): bool
    {
        $normalized = self::normalizeReferenceKey($value);

        if ($normalized === 'autre') {
            return true;
        }

        if ($normalized === '' || ! Schema::hasTable('etablissements')) {
            return false;
        }

        return DB::table('etablissements')
            ->where('actif', true)
            ->where('nom_normalise', $normalized)
            ->exists();
    }

    private static function normalizeReferenceKey(string $value): string
    {
        return Str::of($value)
            ->replace(['’', '`', '´'], "'")
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }

    private static function normalizeUsagerStatus(string $value): string
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
    private static function allowedUsagerStatuses(): array
    {
        return array_values(array_unique(array_merge(self::USAGER_STATUSES, self::USAGER_STATUS_ALIASES)));
    }

    /**
     * @return list<string>
     */
    private static function allowedCategories(): array
    {
        $categories = array_values(self::CATEGORIES_BY_TYPE);
        $categories[] = self::CATEGORY_ALIASES;

        return array_values(array_unique(array_merge(...$categories)));
    }

    private static function personNameRule(): string
    {
        return 'regex:/^[\p{L}\p{M}][\p{L}\p{M}\s.\'\x{2019}-]*$/u';
    }

    private static function organizationNameRule(): string
    {
        return 'regex:/^[\p{L}\p{M}0-9][\p{L}\p{M}0-9\s.\'\x{2019},()\/-]*$/u';
    }
}
