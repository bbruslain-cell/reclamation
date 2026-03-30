<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'name')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->string('name', 50)->nullable()->after('code');
            });
        }

        if (!Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->string('guard_name')->default('web')->after('libelle');
            });
        }

        if (!Schema::hasColumn('permissions', 'name')) {
            Schema::table('permissions', function (Blueprint $table): void {
                $table->string('name', 100)->nullable()->after('code');
            });
        }

        if (!Schema::hasColumn('permissions', 'guard_name')) {
            Schema::table('permissions', function (Blueprint $table): void {
                $table->string('guard_name')->default('web')->after('libelle');
            });
        }

        DB::table('roles')
            ->whereNull('name')
            ->update([
                'name' => DB::raw('code'),
                'guard_name' => 'web',
            ]);

        DB::table('roles')
            ->where(function ($query): void {
                $query->where('guard_name', '')->orWhereNull('guard_name');
            })
            ->update(['guard_name' => 'web']);

        DB::table('permissions')
            ->whereNull('name')
            ->update([
                'name' => DB::raw('code'),
                'guard_name' => 'web',
            ]);

        DB::table('permissions')
            ->where(function ($query): void {
                $query->where('guard_name', '')->orWhereNull('guard_name');
            })
            ->update(['guard_name' => 'web']);

        Schema::table('roles', function (Blueprint $table): void {
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->unique(['name', 'guard_name'], 'permissions_name_guard_name_unique');
        });

        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table): void {
                $table->foreignId('id_permission')
                    ->constrained('permissions', 'id_permission')
                    ->cascadeOnDelete();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->primary(
                    ['id_permission', 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            });
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table): void {
                $table->foreignId('id_role')
                    ->constrained('roles', 'id_role')
                    ->cascadeOnDelete();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->primary(
                    ['id_role', 'model_id', 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            });
        }

        foreach (DB::table('utilisateur_role')->get() as $assignment) {
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'id_role' => (int) $assignment->id_role,
                    'model_type' => 'App\\Models\\Utilisateur',
                    'model_id' => (int) $assignment->id_utilisateur,
                ],
                [
                    'id_role' => (int) $assignment->id_role,
                    'model_type' => 'App\\Models\\Utilisateur',
                    'model_id' => (int) $assignment->id_utilisateur,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');

        if (Schema::hasColumn('permissions', 'guard_name')) {
            Schema::table('permissions', function (Blueprint $table): void {
                $table->dropUnique('permissions_name_guard_name_unique');
                $table->dropColumn(['name', 'guard_name']);
            });
        }

        if (Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->dropUnique('roles_name_guard_name_unique');
                $table->dropColumn(['name', 'guard_name']);
            });
        }
    }
};
