<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private array $indexes = [
        ['affectations', 'id_demande', 'idx_affectations_demande'],
        ['affectations', 'id_service', 'idx_affectations_service'],
        ['affectations', 'id_utilisateur', 'idx_affectations_utilisateur'],
        ['config_sla', 'id_direction', 'idx_config_sla_direction'],
        ['config_sla', 'id_service', 'idx_config_sla_service'],
        ['demande_piece_jointe', 'id_piece_jointe', 'idx_demande_piece_jointe_piece'],
        ['demandes', 'id_agent_accueil', 'idx_demandes_agent_accueil'],
        ['demandes', 'id_agent_direction', 'idx_demandes_agent_direction'],
        ['demandes', 'id_agent_traitant', 'idx_demandes_agent_traitant'],
        ['demandes', 'id_config_sla', 'idx_demandes_config_sla'],
        ['demandes', 'id_usager', 'idx_demandes_usager'],
        ['exports', 'id_format_export', 'idx_exports_format_export'],
        ['exports', 'id_utilisateur', 'idx_exports_utilisateur'],
        ['historique_actions', 'ancien_statut_id', 'idx_historique_ancien_statut'],
        ['historique_actions', 'id_agent_associe', 'idx_historique_agent_associe'],
        ['historique_actions', 'nouveau_statut_id', 'idx_historique_nouveau_statut'],
        ['notifications', 'id_demande', 'idx_notifications_demande'],
        ['notifications', 'id_emetteur', 'idx_notifications_emetteur'],
        ['notifications', 'id_reponse', 'idx_notifications_reponse'],
        ['notifications', 'id_statut_notif', 'idx_notifications_statut_notif'],
        ['notifications', 'id_type_notif', 'idx_notifications_type_notif'],
        ['perimetre_direction', 'id_direction', 'idx_perimetre_direction_direction'],
        ['perimetre_service', 'id_service', 'idx_perimetre_service_service'],
        ['permission_role', 'id_role', 'idx_permission_role_role'],
        ['pieces_jointes', 'id_uploadeur', 'idx_pieces_jointes_uploadeur'],
        ['reponse_piece_jointe', 'id_piece_jointe', 'idx_reponse_piece_jointe_piece'],
        ['reponses', 'id_envoyeur', 'idx_reponses_envoyeur'],
        ['reponses', 'id_redacteur', 'idx_reponses_redacteur'],
        ['reponses', 'id_type_reponse', 'idx_reponses_type_reponse'],
        ['services', 'id_direction', 'idx_services_direction'],
        ['utilisateur_role', 'id_role', 'idx_utilisateur_role_role'],
        ['utilisateurs', 'id_service', 'idx_utilisateurs_service'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $column, $index]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || $this->indexExists($table, $index)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column, $index): void {
                $blueprint->index($column, $index);
            });
        }

        $this->syncTrackingGenerators();
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes) as [$table, , $index]) {
            if (!Schema::hasTable($table) || !$this->indexExists($table, $index)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropIndex($index);
            });
        }
    }

    private function syncTrackingGenerators(): void
    {
        if (!Schema::hasTable('demandes')) {
            return;
        }

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

        if (Schema::hasTable('demandes_numero_compteurs')) {
            foreach ($maxByYear as $year => $value) {
                $counter = DB::table('demandes_numero_compteurs')->where('annee', $year)->first();

                if (!$counter) {
                    DB::table('demandes_numero_compteurs')->insert([
                        'annee' => $year,
                        'valeur' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                if ((int) $counter->valeur < $value) {
                    DB::table('demandes_numero_compteurs')
                        ->where('annee', $year)
                        ->update([
                            'valeur' => $value,
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        if (DB::getDriverName() !== 'pgsql' || $maxValue <= 0) {
            return;
        }

        DB::statement('CREATE SEQUENCE IF NOT EXISTS demandes_numero_seq START 1');

        $current = DB::selectOne('SELECT last_value FROM demandes_numero_seq');
        if ((int) ($current->last_value ?? 0) < $maxValue) {
            DB::selectOne("SELECT setval('demandes_numero_seq', ?, true)", [$maxValue]);
        }
    }

    private function indexExists(string $table, string $index): bool
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
};
