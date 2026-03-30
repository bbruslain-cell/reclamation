<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contrainte déjà présente en base — rien à faire
    }

    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropUnique(['numero_suivi']);
        });
    }
};