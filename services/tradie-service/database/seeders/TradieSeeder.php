<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;
use App\Models\TradieProfile;

class TradieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Plumbing', 'description' => 'Pipes, taps, leaks'],
            ['name' => 'Electrical', 'description' => 'Wiring, lights, safety'],
            ['name' => 'Carpentry', 'description' => 'Woodwork, furniture, repairs'],
            ['name' => 'Painting', 'description' => 'Interior and exterior painting'],
            ['name' => 'Landscaping', 'description' => 'Garden, lawn, outdoor'],
        ];
        $categoryIds = ServiceCategory::pluck('id')->all();
        if (empty($categoryIds)) {
            foreach ($categories as $c) {
                $cat = ServiceCategory::firstOrCreate(['name' => $c['name']], $c);
                $categoryIds[] = $cat->id;
            }
        }

        if (TradieProfile::count() === 0) {
            for ($i = 1; $i <= 5; $i++) {
                $profile = TradieProfile::create([
                'account_id'   => $i,
                'business_name'=> "Tradie Biz $i",
                'about'        => 'Experienced tradie ready to help',
                'postcode'     => (string)rand(2000, 2999),
                'base_rate'    => rand(50, 150),
                ]);
                $attach = array_rand(array_flip($categoryIds), rand(1, min(3, count($categoryIds))));
                $profile->categories()->sync((array)$attach);
            }
        }
    }
}
