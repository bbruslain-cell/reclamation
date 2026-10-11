<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('etablissements', function (Blueprint $table): void {
            $table->id('id_etablissement');
            $table->string('nom');
            $table->string('nom_normalise')->unique();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['actif', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissements');
    }
};
