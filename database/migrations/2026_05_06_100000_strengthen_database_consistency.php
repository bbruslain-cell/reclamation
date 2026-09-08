<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->deduplicateResponses();

        Schema::table('reponses', function (Blueprint $table): void {
            $table->unique('id_demande', 'uq_reponses_demande_unique');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            if (!Schema::hasColumn('notifications', 'id_reponse')) {
                $table->foreignId('id_reponse')
                    ->nullable()
                    ->after('id_demande')
                    ->constrained('reponses', 'id_reponse')
                    ->nullOnDelete();
            }
        });

        Schema::table('config_sla', function (Blueprint $table): void {
            if (!Schema::hasColumn('config_sla', 'id_direction')) {
                $table->foreignId('id_direction')
                    ->nullable()
                    ->after('nom')
                    ->constrained('directions', 'id_direction')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('config_sla', 'id_service')) {
                $table->foreignId('id_service')
                    ->nullable()
                    ->after('id_direction')
                    ->constrained('services', 'id_service')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('config_sla', function (Blueprint $table): void {
            if (Schema::hasColumn('config_sla', 'id_service')) {
                $table->dropForeign(['id_service']);
                $table->dropColumn('id_service');
            }

            if (Schema::hasColumn('config_sla', 'id_direction')) {
                $table->dropForeign(['id_direction']);
                $table->dropColumn('id_direction');
            }
        });

        Schema::table('notifications', function (Blueprint $table): void {
            if (Schema::hasColumn('notifications', 'id_reponse')) {
                $table->dropForeign(['id_reponse']);
                $table->dropColumn('id_reponse');
            }
        });

        Schema::table('reponses', function (Blueprint $table): void {
            $table->dropUnique('uq_reponses_demande_unique');
        });
    }

    private function deduplicateResponses(): void
    {
        $duplicateDemandIds = DB::table('reponses')
            ->select('id_demande')
            ->groupBy('id_demande')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('id_demande');

        foreach ($duplicateDemandIds as $demandId) {
            $responses = DB::table('reponses')
                ->where('id_demande', $demandId)
                ->orderByDesc('numero_version')
                ->orderByDesc('id_reponse')
                ->get(['id_reponse']);

            $keepId = (int) ($responses->first()->id_reponse ?? 0);
            $duplicateIds = $responses
                ->pluck('id_reponse')
                ->map(static fn ($value) => (int) $value)
                ->filter(static fn (int $responseId) => $responseId !== $keepId)
                ->values();

            if ($keepId === 0 || $duplicateIds->isEmpty()) {
                continue;
            }

            $pieceIds = DB::table('reponse_piece_jointe')
                ->whereIn('id_reponse', $duplicateIds->all())
                ->pluck('id_piece_jointe')
                ->map(static fn ($value) => (int) $value)
                ->unique();

            foreach ($pieceIds as $pieceId) {
                DB::table('reponse_piece_jointe')->updateOrInsert([
                    'id_reponse' => $keepId,
                    'id_piece_jointe' => $pieceId,
                ]);
            }

            DB::table('reponse_piece_jointe')
                ->whereIn('id_reponse', $duplicateIds->all())
                ->delete();

            DB::table('reponses')
                ->whereIn('id_reponse', $duplicateIds->all())
                ->delete();
        }
    }
};
