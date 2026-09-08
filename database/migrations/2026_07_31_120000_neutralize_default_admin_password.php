<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $adminEmail = strtolower(trim((string) config('deployment.initial_admin.email', 'admin@anbg.ga')));
        $admin = DB::table('utilisateurs')
            ->where('email', $adminEmail)
            ->first(['id_utilisateur', 'password_hash']);

        $knownDefaultPasswords = ['Admin@123456', 'ChangeMe@123'];
        $usesKnownDefaultPassword = $admin && collect($knownDefaultPasswords)
            ->contains(fn (string $password): bool => Hash::check($password, (string) $admin->password_hash));

        if (! $usesKnownDefaultPassword) {
            return;
        }

        $replacementPassword = trim((string) config('deployment.initial_admin.password', ''));
        if (
            $replacementPassword !== ''
            && (in_array($replacementPassword, $knownDefaultPasswords, true) || strlen($replacementPassword) < 12)
        ) {
            throw new RuntimeException('INITIAL_ADMIN_PASSWORD must be unique and at least 12 characters long.');
        }

        if ($replacementPassword === '' && app()->environment('production')) {
            throw new RuntimeException(
                'INITIAL_ADMIN_PASSWORD is required to replace the insecure production administrator password.'
            );
        }

        if ($replacementPassword === '') {
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
