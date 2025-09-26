<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed demo data when explicitly enabled
        if (env('SEED_DEMO', false)) {
            $this->call([
                BookingSeeder::class,
            ]);
        }
    }
}
