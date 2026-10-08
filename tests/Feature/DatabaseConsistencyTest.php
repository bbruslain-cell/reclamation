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

    public function test_database_has_indexes_for_workflow_foreign_keys(): void
    {
        $indexes = [
            ['affectations', 'idx_affectations_demande'],
            ['affectations', 'idx_affectations_service'],
            ['affectations', 'idx_affectations_utilisateur'],
            ['demandes', 'idx_demandes_agent_accueil'],
            ['demandes', 'idx_demandes_agent_direction'],
            ['demandes', 'idx_demandes_agent_traitant'],
            ['demandes', 'idx_demandes_config_sla'],
            ['demandes', 'idx_demandes_usager'],
            ['services', 'idx_services_direction'],
            ['utilisateurs', 'idx_utilisateurs_service'],
            ['reponses', 'idx_reponses_redacteur'],
            ['reponses', 'idx_reponses_envoyeur'],
            ['notifications', 'idx_notifications_demande'],
            ['notifications', 'idx_notifications_reponse'],
        ];

        foreach ($indexes as [$table, $index]) {
            $this->assertTrue(
                $this->databaseIndexExists($table, $index),
                "Index {$index} manquant sur {$table}."
            );
        }
    }

    public function test_tracking_number_generators_are_not_behind_existing_demands(): void
    {
        $this->seed();

        $maxByYear = [];
        $maxValue = 0;

        foreach (DB::table('demandes')->pluck('numero_suivi') as $trackingNumber) {
            if (!is_string($trackingNumber) || !preg_match('/^ANBG-(\d{4})-(\d+)$/', $trackingNumber, $matches)) {
                continue;
            }

            $year = (int) $matches[1];
            $value = (int) $matches[2];
            $maxByYear[$year] = max($maxByYear[$year] ?? 0, $value);
            $maxValue = max($maxValue, $value);
        }

        foreach ($maxByYear as $year => $value) {
            $counter = DB::table('demandes_numero_compteurs')->where('annee', $year)->first();

            $this->assertNotNull($counter, "Compteur absent pour l'annee {$year}.");
            $this->assertGreaterThanOrEqual($value, (int) $counter->valeur);
        }

        if (DB::getDriverName() === 'pgsql' && $maxValue > 0) {
            $sequence = DB::selectOne('SELECT last_value FROM demandes_numero_seq');

            $this->assertGreaterThanOrEqual($maxValue, (int) ($sequence->last_value ?? 0));
        }
    }

    private function databaseIndexExists(string $table, string $index): bool
    {
        return match (DB::getDriverName()) {
            'pgsql' => (bool) DB::selectOne(
                'SELECT EXISTS (SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?) AS exists',
                [$table, $index]
            )->exists,
            'sqlite' => collect(DB::select("PRAGMA index_list('".$table."')"))
                ->contains(fn (object $row): bool => (string) ($row->name ?? '') === $index),
            'mysql', 'mariadb' => DB::table('information_schema.statistics')
                ->where('table_schema', DB::connection()->getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists(),
            default => false,
        };
    }
}
