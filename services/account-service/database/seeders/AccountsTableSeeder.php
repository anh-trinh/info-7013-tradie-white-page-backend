<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AccountsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure at least 10 generic accounts exist
        $current = User::count();
        $target = 10;
        if ($current < $target) {
            User::factory()->count($target - $current)->create();
        }

        // Create 30 deterministic tradie accounts with fixed IDs 101..130 for cross-service linking
        $password = password_hash('Password123!', PASSWORD_BCRYPT);
        $services = ['Plumbing','Electrical','Carpentry','Painting','Landscaping','Roofing'];
        $i = 0;
        for ($svc = 0; $svc < count($services); $svc++) {
            for ($t = 1; $t <= 5; $t++) {
                $i++;
                $id = 100 + $i; // 101..130
                $service = $services[$svc];
                $first = ['Alex','Jordan','Taylor','Morgan','Casey'][$t-1];
                $last = $service;
                $emailLocal = strtolower($service).$t;
                $row = [
                    'id'           => $id,
                    'first_name'   => $first,
                    'last_name'    => $last,
                    'email'        => $emailLocal.'@example.com',
                    'password'     => $password,
                    'phone_number' => sprintf('040000%04d', $i),
                    'role'         => 'tradie',
                    'status'       => 'active',
                ];
                DB::table('accounts')->updateOrInsert(['id' => $id], $row);
            }
        }
    }
}
