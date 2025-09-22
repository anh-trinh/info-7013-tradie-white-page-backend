<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;
use App\Models\TradieProfile;

class TradieSeeder extends Seeder
{
    public function run(): void
    {
        // Clean existing data to keep dataset deterministic (dev/demo only)
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        \Illuminate\Support\Facades\DB::table('tradie_services')->truncate();
        \Illuminate\Support\Facades\DB::table('tradie_profiles')->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Define 6 service categories (no Cleaning, include Roofing)
        $serviceDefs = [
            ['name' => 'Plumbing',    'description' => 'Pipes, taps, leaks'],
            ['name' => 'Electrical',  'description' => 'Wiring, lights, safety'],
            ['name' => 'Carpentry',   'description' => 'Woodwork, furniture, repairs'],
            ['name' => 'Painting',    'description' => 'Interior and exterior painting'],
            ['name' => 'Landscaping', 'description' => 'Garden, lawn, outdoor'],
            ['name' => 'Roofing',     'description' => 'Roof repairs, gutters, tiles'],
        ];

        // Ensure categories exist and build a map
        $categories = [];
        foreach ($serviceDefs as $c) {
            $categories[$c['name']] = ServiceCategory::firstOrCreate(['name' => $c['name']], $c);
        }

        // Naming helpers for realistic business names
        $brandPrefixes = ['Apex', 'Harbour', 'Prime', 'Metro', 'Skyline', 'GreenLeaf', 'Rapid', 'Summit', 'Elite', 'Northern'];
        $brandSuffixes = [
            'Plumbing'    => ['Plumbing', 'Pipeworks', 'Flow', 'Drainage'],
            'Electrical'  => ['Electrical', 'Electrics', 'Power', 'Wiring'],
            'Carpentry'   => ['Carpentry', 'Joinery', 'Woodworks', 'Timber'],
            'Painting'    => ['Painting', 'Painters', 'Coatings', 'Decor'],
            'Landscaping' => ['Landscaping', 'Gardens', 'Outdoor', 'Horticulture'],
            'Roofing'     => ['Roofing', 'Roof Care', 'Gutters', 'Tiles'],
        ];

        $aboutByService = [
            'Plumbing' => 'Trusted plumbing experts serving Sydney.',
            'Electrical' => 'Licensed electricians for homes and businesses.',
            'Carpentry' => 'Quality carpentry and joinery services.',
            'Painting' => 'Professional interior and exterior painting.',
            'Landscaping' => 'Beautiful gardens and outdoor spaces.',
            'Roofing' => 'Reliable roofing repairs and maintenance.',
        ];

        $baseRateByService = [
            'Plumbing' => 90,
            'Electrical' => 95,
            'Carpentry' => 85,
            'Painting' => 80,
            'Landscaping' => 75,
            'Roofing' => 100,
        ];

        // Build 5 tradies per service (3 with 2000, 2 with 3000) using account IDs 101..130
        $profiles = [];
        $accId = 100; // will increment to 101..130
        foreach ($serviceDefs as $sIdx => $svc) {
            $service = $svc['name'];
            $catId = $categories[$service]->id;
            for ($i = 0; $i < 5; $i++) {
                $accId++;
                $prefix = $brandPrefixes[($sIdx + $i) % count($brandPrefixes)];
                $suffix = $brandSuffixes[$service][($sIdx + $i) % count($brandSuffixes[$service])];
                $biz = trim("$prefix $suffix");
                $postcode = $i < 3 ? '2000' : '3000';
                $rate = (string)($baseRateByService[$service] + (($i * 5) % 15));
                $about = $aboutByService[$service];

                $profiles[] = [
                    'account_id' => $accId,
                    'business_name' => $biz,
                    'about' => $about,
                    'postcode' => $postcode,
                    'base_rate' => $rate,
                    'category_id' => $catId,
                ];
            }
        }

        // Upsert profiles and attach category
        foreach ($profiles as $p) {
            $profile = TradieProfile::updateOrCreate(
                ['account_id' => $p['account_id']],
                [
                    'business_name' => $p['business_name'],
                    'about'         => $p['about'],
                    'postcode'      => $p['postcode'],
                    'base_rate'     => $p['base_rate'],
                ]
            );
            $profile->categories()->sync([$p['category_id']]);
        }
    }
}
