<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EtablissementSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/etablissements-superieurs.json');
        $json = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($path));
        $establishments = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
        $now = now();

        $activeEstablishments = collect($establishments)
            ->filter(fn (mixed $name): bool => is_string($name) && $this->shouldImport($name))
            ->unique(fn (string $name): string => $this->normalize($name))
            ->values();

        if ($activeEstablishments->isNotEmpty()) {
            DB::table('etablissements')
                ->whereNotIn('nom_normalise', $activeEstablishments->map(fn (string $name): string => $this->normalize($name))->all())
                ->delete();
        }

        $activeEstablishments
            ->chunk(500)
            ->each(function ($chunk) use ($now): void {
                $rows = $chunk->map(fn (string $name): array => [
                    'nom' => trim($name),
                    'nom_normalise' => $this->normalize($name),
                    'actif' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('etablissements')->upsert(
                    $rows,
                    ['nom_normalise'],
                    ['nom', 'actif', 'updated_at']
                );
            });
    }

    private function shouldImport(string $value): bool
    {
        $normalized = $this->normalize($value);

        if ($normalized === '' || $normalized === 'non scolarise') {
            return false;
        }

        return ! preg_match('/(^| )lycees?( |$)/', $normalized)
            && ! preg_match('/(^| )lgt( |$)/', $normalized);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->replace(['’', '`', '´'], "'")
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }
}
