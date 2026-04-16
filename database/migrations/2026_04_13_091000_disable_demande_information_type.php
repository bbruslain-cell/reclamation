<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('code', 'demande_information')
            ->update([
                'actif' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('code', 'demande_information')
            ->update([
                'actif' => true,
                'updated_at' => now(),
            ]);
    }
};
