<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('config_sla')
            ->where('actif', true)
            ->update([
                'nom' => 'SLA ANBG 24h Ouvrees',
                'delai_max_heures' => 24,
                'updated_at' => now(),
            ]);

        $thresholds = [
            'default' => ['warning' => 12, 'deadline' => 24],
            'accueil' => ['warning' => 4, 'deadline' => 8],
            'chef' => ['warning' => 8, 'deadline' => 16],
            'agent' => ['warning' => 8, 'deadline' => 16],
        ];

        foreach ($thresholds as $code => $payload) {
            DB::table('parametres')
                ->where('famille', 'seuil_alerte')
                ->where('code', $code)
                ->update([
                    'metadata_json' => json_encode($payload),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('config_sla')
            ->where('actif', true)
            ->update([
                'nom' => 'SLA ANBG 72h Ouvrees',
                'delai_max_heures' => 72,
                'updated_at' => now(),
            ]);

        $thresholds = [
            'default' => ['vert' => 12, 'orange' => 18, 'rouge' => 24],
            'accueil' => ['vert' => 12, 'orange' => 18, 'rouge' => 24],
            'chef' => ['vert' => 24, 'orange' => 36, 'rouge' => 48],
            'agent' => ['vert' => 24, 'orange' => 36, 'rouge' => 48],
        ];

        foreach ($thresholds as $code => $payload) {
            DB::table('parametres')
                ->where('famille', 'seuil_alerte')
                ->where('code', $code)
                ->update([
                    'metadata_json' => json_encode($payload),
                    'updated_at' => now(),
                ]);
        }
    }
};
