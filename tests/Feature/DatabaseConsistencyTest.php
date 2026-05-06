<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use App\Services\RoleSyncService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_sync_keeps_legacy_and_spatie_role_pivots_consistent(): void
    {
        $this->seed();

        $user = Utilisateur::query()->where('email', 'agent.daf@anbg.ga')->firstOrFail();
        $roleIds = DB::table('roles')
            ->whereIn('code', ['agent', 'ciq'])
            ->pluck('id_role')
            ->map(static fn ($value) => (int) $value)
            ->all();

        app(RoleSyncService::class)->syncUserRolesByIds($user, $roleIds);

        $legacyRoleIds = DB::table('utilisateur_role')
            ->where('id_utilisateur', $user->id_utilisateur)
            ->orderBy('id_role')
            ->pluck('id_role')
            ->map(static fn ($value) => (int) $value)
            ->all();

        $spatieRoleIds = DB::table('model_has_roles')
            ->where('model_type', Utilisateur::class)
            ->where('model_id', $user->id_utilisateur)
            ->orderBy('id_role')
            ->pluck('id_role')
            ->map(static fn ($value) => (int) $value)
            ->all();

        $this->assertSame($legacyRoleIds, $spatieRoleIds);
    }

    public function test_database_enforces_a_single_response_per_demand(): void
    {
        $this->seed();

        $response = DB::table('reponses')->first();
        $this->assertNotNull($response);

        $this->expectException(QueryException::class);

        DB::table('reponses')->insert([
            'id_demande' => (int) $response->id_demande,
            'numero_version' => ((int) $response->numero_version) + 1,
            'id_type_reponse' => (int) $response->id_type_reponse,
            'contenu_reponse' => 'Tentative de deuxieme reponse pour la meme demande.',
            'id_redacteur' => (int) $response->id_redacteur,
            'date_redaction' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_exposes_response_notification_and_sla_scope_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('notifications', 'id_reponse'));
        $this->assertTrue(Schema::hasColumn('config_sla', 'id_direction'));
        $this->assertTrue(Schema::hasColumn('config_sla', 'id_service'));
    }
}
