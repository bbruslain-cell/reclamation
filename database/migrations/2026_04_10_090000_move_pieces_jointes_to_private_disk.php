<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->moveAttachments('public', 'local');
    }

    public function down(): void
    {
        $this->moveAttachments('local', 'public');
    }

    private function moveAttachments(string $fromDisk, string $toDisk): void
    {
        DB::table('pieces_jointes')
            ->select('id_piece_jointe', 'chemin_fichier')
            ->orderBy('id_piece_jointe')
            ->chunkById(100, function ($pieces) use ($fromDisk, $toDisk): void {
                foreach ($pieces as $piece) {
                    $path = trim((string) ($piece->chemin_fichier ?? ''));
                    if ($path === '' || !Storage::disk($fromDisk)->exists($path)) {
                        continue;
                    }

                    if (!Storage::disk($toDisk)->exists($path)) {
                        Storage::disk($toDisk)->put($path, Storage::disk($fromDisk)->get($path));
                    }

                    Storage::disk($fromDisk)->delete($path);
                }
            }, 'id_piece_jointe', 'id_piece_jointe');
    }
};
