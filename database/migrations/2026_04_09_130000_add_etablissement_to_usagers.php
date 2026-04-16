<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usagers', function (Blueprint $table): void {
            $table->string('etablissement', 255)->nullable()->after('pays');
        });
    }

    public function down(): void
    {
        Schema::table('usagers', function (Blueprint $table): void {
            $table->dropColumn('etablissement');
        });
    }
};
