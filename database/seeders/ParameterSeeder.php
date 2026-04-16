<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParameterSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['famille' => 'type_demande', 'code' => 'reclamation', 'libelle' => 'Reclamation', 'ordre_affichage' => 1],

            ['famille' => 'statut_demande', 'code' => 'nouvelle', 'libelle' => 'Recu', 'ordre_affichage' => 1],
            ['famille' => 'statut_demande', 'code' => 'affectee_service', 'libelle' => 'Affectee au service', 'ordre_affichage' => 2],
            ['famille' => 'statut_demande', 'code' => 'affectee_agent', 'libelle' => 'Affectee a un agent', 'ordre_affichage' => 3],
            ['famille' => 'statut_demande', 'code' => 'reponse_prete', 'libelle' => 'Reponse redigee', 'ordre_affichage' => 4],
            ['famille' => 'statut_demande', 'code' => 'cloturee', 'libelle' => 'Cloturee', 'ordre_affichage' => 5],

            ['famille' => 'type_reponse', 'code' => 'directe', 'libelle' => 'Directe', 'ordre_affichage' => 1],
            ['famille' => 'type_reponse', 'code' => 'via_direction', 'libelle' => 'Via direction', 'ordre_affichage' => 2],
            ['famille' => 'type_reponse', 'code' => 'finale', 'libelle' => 'Finale', 'ordre_affichage' => 3],

            ['famille' => 'type_notif', 'code' => 'accuse_reception', 'libelle' => 'Accuse de reception', 'ordre_affichage' => 1],
            ['famille' => 'type_notif', 'code' => 'alerte_sla', 'libelle' => 'Alerte SLA', 'ordre_affichage' => 2],
            ['famille' => 'type_notif', 'code' => 'reponse_envoyee', 'libelle' => 'Reponse envoyee', 'ordre_affichage' => 3],
            ['famille' => 'type_notif', 'code' => 'interne', 'libelle' => 'Interne', 'ordre_affichage' => 4],

            ['famille' => 'statut_notif', 'code' => 'en_attente', 'libelle' => 'En attente', 'ordre_affichage' => 1],
            ['famille' => 'statut_notif', 'code' => 'succes', 'libelle' => 'Succes', 'ordre_affichage' => 2],
            ['famille' => 'statut_notif', 'code' => 'echec', 'libelle' => 'Echec', 'ordre_affichage' => 3],

            ['famille' => 'format_export', 'code' => 'excel', 'libelle' => 'Excel', 'ordre_affichage' => 1],
            ['famille' => 'format_export', 'code' => 'pdf', 'libelle' => 'PDF', 'ordre_affichage' => 2],

            [
                'famille' => 'seuil_alerte',
                'code' => 'default',
                'libelle' => 'Seuils SLA par defaut',
                'ordre_affichage' => 1,
                'metadata_json' => json_encode(['vert' => 12, 'orange' => 18, 'rouge' => 24]),
            ],
            [
                'famille' => 'seuil_alerte',
                'code' => 'accueil',
                'libelle' => 'Seuils accueil',
                'ordre_affichage' => 2,
                'metadata_json' => json_encode(['vert' => 12, 'orange' => 18, 'rouge' => 24]),
            ],
            [
                'famille' => 'seuil_alerte',
                'code' => 'chef',
                'libelle' => 'Seuils chef de service',
                'ordre_affichage' => 3,
                'metadata_json' => json_encode(['vert' => 24, 'orange' => 36, 'rouge' => 48]),
            ],
            [
                'famille' => 'seuil_alerte',
                'code' => 'agent',
                'libelle' => 'Seuils agent',
                'ordre_affichage' => 4,
                'metadata_json' => json_encode(['vert' => 24, 'orange' => 36, 'rouge' => 48]),
            ],
        ];

        foreach ($rows as $row) {
            DB::table('parametres')->updateOrInsert(
                ['famille' => $row['famille'], 'code' => $row['code']],
                [
                    'libelle' => $row['libelle'],
                    'ordre_affichage' => $row['ordre_affichage'],
                    'actif' => true,
                    'date_debut_validite' => now()->toDateString(),
                    'metadata_json' => $row['metadata_json'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $activeStatusCodes = [
            'nouvelle',
            'affectee_service',
            'affectee_agent',
            'reponse_prete',
            'cloturee',
        ];

        DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('code', 'reclamation')
            ->update([
                'actif' => true,
                'updated_at' => now(),
            ]);

        DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('code', 'demande_information')
            ->update([
                'actif' => false,
                'updated_at' => now(),
            ]);

        DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->whereNotIn('code', $activeStatusCodes)
            ->update([
                'actif' => false,
                'updated_at' => now(),
            ]);
    }
}
