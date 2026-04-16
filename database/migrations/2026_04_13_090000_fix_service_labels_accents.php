<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private array $accentedLabels = [
        'UCAS' => 'Unité Courrier, Accueil et Sécurité',
        'SCIQ' => 'Service Contrôle Interne et Qualité',
        'CS_SIRS' => 'Systèmes d Informations, Réseaux et Sécurité',
        'CS_AMG' => 'Approvisionnement et Moyens Généraux',
        'CS_SNB' => 'Étudiants non Boursiers',
        'CS_SENB' => 'Étudiants Boursiers',
    ];

    private array $plainLabels = [
        'UCAS' => 'Unite Courrier, Accueil et Securite',
        'SCIQ' => 'Service Controle Interne et Qualite',
        'CS_SIRS' => 'Systemes d Informations, Reseaux et Securite',
        'CS_AMG' => 'Approvisionnement et Moyens Generaux',
        'CS_SNB' => 'Etudiants non Boursiers',
        'CS_SENB' => 'Etudiants Boursiers',
    ];

    public function up(): void
    {
        $this->updateLabels($this->accentedLabels);
    }

    public function down(): void
    {
        $this->updateLabels($this->plainLabels);
    }

    private function updateLabels(array $labels): void
    {
        foreach ($labels as $code => $libelle) {
            DB::table('services')
                ->where('code', $code)
                ->update([
                    'libelle' => $libelle,
                    'updated_at' => now(),
                ]);
        }
    }
};
