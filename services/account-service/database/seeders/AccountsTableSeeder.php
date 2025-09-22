<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AccountsTableSeeder extends Seeder
{
    public function run(): void
    {
        $current = User::count();
        $target = 10;
        if ($current < $target) {
            User::factory()->count($target - $current)->create();
        }
    }
}
