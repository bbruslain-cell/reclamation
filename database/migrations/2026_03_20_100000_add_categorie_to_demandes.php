<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('demandes', 'categorie')) {
            Schema::table('demandes', function (Blueprint $table): void {
                $table->string('categorie')->nullable()->after('objet');
            });
        }

        $categoryActions = DB::table('historique_actions')
            ->select('id_demande', 'commentaire')
            ->where('type_action', 'categorie_usager')
            ->whereNotNull('commentaire')
            ->orderBy('id_action')
            ->get()
            ->groupBy('id_demande');

        foreach ($categoryActions as $demandId => $actions) {
            $firstAction = $actions->first();
            if (!$firstAction || !is_string($firstAction->commentaire)) {
                continue;
            }

            if (!preg_match('/:\s*(.+)$/u', $firstAction->commentaire, $matches)) {
                continue;
            }

            $categorie = trim((string) ($matches[1] ?? ''));
            if ($categorie === '') {
                continue;
            }

            DB::table('demandes')
                ->where('id_demande', (int) $demandId)
                ->where(function ($query) {
                    $query->whereNull('categorie')
                        ->orWhere('categorie', '');
                })
                ->update([
                    'categorie' => $categorie,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demandes', 'categorie')) {
            Schema::table('demandes', function (Blueprint $table): void {
                $table->dropColumn('categorie');
            });
        }
    }
};
