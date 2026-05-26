<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('config_sla')->where('actif', true)->update([
            'actif' => false,
            'updated_at' => now(),
        ]);

        DB::table('config_sla')->updateOrInsert(
            ['nom' => 'SUIVI ANBG 24h Ouvrées'],
            [
                'delai_max_heures' => 24,
                'fuseau_horaire' => 'Africa/Libreville',
                'actif' => true,
                'date_debut_validite' => now()->toDateString(),
                'date_fin_validite' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $configId = DB::table('config_sla')
            ->where('nom', 'SUIVI ANBG 24h Ouvrées')
            ->value('id_config_sla');

        if (!$configId) {
            return;
        }

        DB::table('sla_jours_ouvres')->where('id_config_sla', $configId)->delete();

        for ($day = 1; $day <= 5; $day++) {
            DB::table('sla_jours_ouvres')->insert([
                'id_config_sla' => $configId,
                'jour_semaine_iso' => $day,
                'heure_debut' => '07:30:00',
                'heure_fin' => '15:30:00',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
