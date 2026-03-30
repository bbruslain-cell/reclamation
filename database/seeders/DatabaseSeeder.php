<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ParameterSeeder::class,
            AccessControlSeeder::class,
            OrganizationSeeder::class,
            SlaSeeder::class,
            DemoDemandSeeder::class,
            DemoTraceabilitySeeder::class,
        ]);
    }
}
