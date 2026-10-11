<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ParameterSeeder::class);

        $activeSlaId = DB::table('config_sla')
            ->where('actif', true)
            ->value('id_config_sla');

        $activeWorkingDays = $activeSlaId
            ? DB::table('sla_jours_ouvres')
                ->where('id_config_sla', $activeSlaId)
                ->where('actif', true)
                ->count()
            : 0;

        if (!$activeSlaId || $activeWorkingDays === 0) {
            $this->call(SlaSeeder::class);
        }
    }
}
