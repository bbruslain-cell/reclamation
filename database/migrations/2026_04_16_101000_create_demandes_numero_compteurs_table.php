<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_numero_compteurs', function (Blueprint $table): void {
            $table->id('id_compteur');
            $table->unsignedSmallInteger('annee')->unique();
            $table->unsignedInteger('valeur')->default(0);
            $table->timestamps();
        });

        $maxByYear = [];

        foreach (DB::table('demandes')->pluck('numero_suivi') as $trackingNumber) {
            if (!is_string($trackingNumber) || !preg_match('/^ANBG-(\d{4})-(\d+)$/', $trackingNumber, $matches)) {
                continue;
            }

            $year = (int) $matches[1];
            $value = (int) $matches[2];
            $maxByYear[$year] = max($maxByYear[$year] ?? 0, $value);
        }

        foreach ($maxByYear as $year => $value) {
            DB::table('demandes_numero_compteurs')->updateOrInsert(
                ['annee' => $year],
                [
                    'valeur' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_numero_compteurs');
    }
};
