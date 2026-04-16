<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table): void {
            if (Schema::hasColumn('demandes', 'importee')) {
                $table->dropColumn('importee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table): void {
            if (!Schema::hasColumn('demandes', 'importee')) {
                $table->boolean('importee')->default(false);
            }
        });
    }
};
