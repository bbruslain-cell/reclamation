<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $directions = [
            'DG' => 'Direction Générale',
            'DS' => 'Direction de la Scolarité',
            'DAF' => 'Direction Administrative et Financière',
            'DSIC' => 'Direction des Systèmes d’Informations et de la Communication',
        ];

        foreach ($directions as $code => $libelle) {
            DB::table('directions')
                ->where('code', $code)
                ->update([
                    'libelle' => $libelle,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $directions = [
            'DG' => 'Direction Generale',
            'DS' => 'Direction de la Scolarite',
            'DAF' => 'Direction Administrative et Financiere',
            'DSIC' => 'Direction des systemes d informations et de la Communication',
        ];

        foreach ($directions as $code => $libelle) {
            DB::table('directions')
                ->where('code', $code)
                ->update([
                    'libelle' => $libelle,
                    'updated_at' => now(),
                ]);
        }
    }
};
