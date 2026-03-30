<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_jours_feries', function (Blueprint $table) {
            $table->date('date_fin')->nullable()->after('date_ferie');
        });
    }

    public function down(): void
    {
        Schema::table('sla_jours_feries', function (Blueprint $table) {
            $table->dropColumn('date_fin');
        });
    }
};
