<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usagers', function (Blueprint $table): void {
            $table->string('statut_usager', 100)->nullable()->after('telephone');
            $table->string('pays', 120)->nullable()->after('statut_usager');
        });
    }

    public function down(): void
    {
        Schema::table('usagers', function (Blueprint $table): void {
            $table->dropColumn(['statut_usager', 'pays']);
        });
    }
};
