<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $dgDirectionId = DB::table('directions')->where('code', 'DG')->value('id_direction');
        if (!$dgDirectionId) {
            return;
        }

        $this->normalizeService(
            oldCode: 'ACC',
            newCode: 'UCAS',
            newLabel: 'Unite Courrier, Accueil et Securite',
            directionId: (int) $dgDirectionId
        );

        $this->ensureService(
            code: 'SCIQ',
            label: 'Systeme Controle Interne et Qualite',
            directionId: (int) $dgDirectionId
        );

        $this->normalizeService(
            oldCode: 'CAB_DG',
            newCode: 'CABINET_DG',
            newLabel: 'Cabinet DG',
            directionId: (int) $dgDirectionId
        );

        $ucasId = DB::table('services')->where('code', 'UCAS')->value('id_service');
        if ($ucasId) {
            DB::table('utilisateurs')
                ->where('email', 'accueil@anbg.ga')
                ->update([
                    'id_service' => (int) $ucasId,
                    'updated_at' => now(),
                ]);
        }

        $sciqId = DB::table('services')->where('code', 'SCIQ')->value('id_service');
        if ($sciqId) {
            DB::table('utilisateurs')
                ->where('email', 'ciq@anbg.ga')
                ->update([
                    'id_service' => (int) $sciqId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $dgDirectionId = DB::table('directions')->where('code', 'DG')->value('id_direction');
        if (!$dgDirectionId) {
            return;
        }

        $this->normalizeService(
            oldCode: 'UCAS',
            newCode: 'ACC',
            newLabel: 'Service Accueil',
            directionId: (int) $dgDirectionId
        );

        $this->normalizeService(
            oldCode: 'CABINET_DG',
            newCode: 'CAB_DG',
            newLabel: 'Cabinet de la DG',
            directionId: (int) $dgDirectionId
        );

        $accId = DB::table('services')->where('code', 'ACC')->value('id_service');
        if ($accId) {
            DB::table('utilisateurs')
                ->where('email', 'accueil@anbg.ga')
                ->update([
                    'id_service' => (int) $accId,
                    'updated_at' => now(),
                ]);
        }

        DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->update([
                'id_service' => null,
                'updated_at' => now(),
            ]);

        DB::table('services')->where('code', 'SCIQ')->delete();
    }

    private function ensureService(string $code, string $label, int $directionId): void
    {
        DB::table('services')->updateOrInsert(
            ['code' => $code],
            [
                'id_direction' => $directionId,
                'libelle' => $label,
                'actif' => true,
                'date_debut_validite' => now()->toDateString(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function normalizeService(string $oldCode, string $newCode, string $newLabel, int $directionId): void
    {
        $oldId = DB::table('services')->where('code', $oldCode)->value('id_service');
        $newId = DB::table('services')->where('code', $newCode)->value('id_service');

        if (!$oldId && !$newId) {
            $this->ensureService($newCode, $newLabel, $directionId);

            return;
        }

        if ($oldId && !$newId) {
            DB::table('services')
                ->where('id_service', $oldId)
                ->update([
                    'code' => $newCode,
                    'id_direction' => $directionId,
                    'libelle' => $newLabel,
                    'actif' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('services')
            ->where('id_service', $newId)
            ->update([
                'id_direction' => $directionId,
                'libelle' => $newLabel,
                'actif' => true,
                'updated_at' => now(),
            ]);

        if (!$oldId || (int) $oldId === (int) $newId) {
            return;
        }

        DB::table('utilisateurs')->where('id_service', $oldId)->update([
            'id_service' => $newId,
            'updated_at' => now(),
        ]);

        DB::table('demandes')->where('id_service_courant', $oldId)->update([
            'id_service_courant' => $newId,
            'updated_at' => now(),
        ]);

        DB::table('affectations')->where('id_service', $oldId)->update([
            'id_service' => $newId,
            'updated_at' => now(),
        ]);

        DB::table('historique_actions')->where('id_service_associe', $oldId)->update([
            'id_service_associe' => $newId,
            'updated_at' => now(),
        ]);

        $perimeterRows = DB::table('perimetre_service')
            ->where('id_service', $oldId)
            ->get();

        foreach ($perimeterRows as $row) {
            DB::table('perimetre_service')->insertOrIgnore([
                'id_utilisateur' => $row->id_utilisateur,
                'id_service' => $newId,
            ]);
        }

        DB::table('perimetre_service')->where('id_service', $oldId)->delete();
        DB::table('services')->where('id_service', $oldId)->delete();
    }
};
