<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usagers', function (Blueprint $table): void {
            $columns = collect(['telephone', 'qualite', 'matricule'])
                ->filter(fn (string $column) => Schema::hasColumn('usagers', $column))
                ->values()
                ->all();

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('usagers', function (Blueprint $table): void {
            if (!Schema::hasColumn('usagers', 'telephone')) {
                $table->string('telephone', 30)->nullable();
            }

            if (!Schema::hasColumn('usagers', 'qualite')) {
                $table->string('qualite')->nullable();
            }

            if (!Schema::hasColumn('usagers', 'matricule')) {
                $table->string('matricule', 60)->nullable();
            }
        });
    }
};
