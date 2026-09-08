<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ParameterSeeder::class,
            EtablissementSeeder::class,
            AccessControlSeeder::class,
            OrganizationSeeder::class,
            SlaSeeder::class,
        ]);

        if (! app()->environment('production')) {
            $this->call([
                DemoDemandSeeder::class,
                DemoTraceabilitySeeder::class,
            ]);
        }
    }
}
