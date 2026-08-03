<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $adminEmail = (string) env('INITIAL_ADMIN_EMAIL', 'admin@anbg.ga');
        $admin = DB::table('utilisateurs')
            ->where('email', $adminEmail)
            ->first(['id_utilisateur', 'password_hash']);

        if (!$admin || !Hash::check('Admin@123456', (string) $admin->password_hash)) {
            return;
        }

        $replacementPassword = trim((string) env('INITIAL_ADMIN_PASSWORD', ''));
        if ($replacementPassword !== '' && strlen($replacementPassword) < 12) {
            throw new RuntimeException('INITIAL_ADMIN_PASSWORD must be at least 12 characters long.');
        }

        if ($replacementPassword === '' || $replacementPassword === 'Admin@123456') {
            $replacementPassword = Str::random(64);
        }

        DB::table('utilisateurs')
            ->where('id_utilisateur', $admin->id_utilisateur)
            ->update([
                'password_hash' => Hash::make($replacementPassword),
                'changement_mdp_requis' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
