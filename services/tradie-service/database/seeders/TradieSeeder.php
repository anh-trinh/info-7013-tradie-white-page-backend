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

    // Build 6 tradies per service (3 with 2000, 3 with 3000) using account IDs 101..136
        $profiles = [];
        $accId = 100; // will increment to 101..130

        // Realistic contact information data
        $contactNames = [
            ['Michael', 'Johnson'], ['David', 'Williams'], ['James', 'Brown'], ['Robert', 'Jones'], ['Mark', 'Davis'],
            ['Andrew', 'Miller'], ['Paul', 'Wilson'], ['Steve', 'Moore'], ['Chris', 'Taylor'], ['Matt', 'Anderson'],
            ['Tony', 'Thomas'], ['Daniel', 'Jackson'], ['Peter', 'White'], ['Scott', 'Harris'], ['Ryan', 'Martin'],
            ['Ben', 'Thompson'], ['Luke', 'Garcia'], ['Adam', 'Martinez'], ['Sam', 'Robinson'], ['Tom', 'Clark'],
            ['Nick', 'Rodriguez'], ['Josh', 'Lewis'], ['Jake', 'Lee'], ['Alex', 'Walker'], ['Sean', 'Hall'],
            ['Brad', 'Allen'], ['Kyle', 'Young'], ['Dean', 'King'], ['Joel', 'Wright'], ['Carl', 'Lopez']
        ];

        // Australian phone numbers (Sydney/NSW area codes)
        $phoneNumbers = [
            '02 9876 5432', '02 9234 5678', '0412 345 678', '0413 456 789', '0414 567 890',
            '02 9345 6789', '02 9456 7890', '0415 678 901', '0416 789 012', '0417 890 123',
            '02 9567 8901', '02 9678 9012', '0418 901 234', '0419 012 345', '0412 123 456',
            '02 9789 0123', '02 9890 1234', '0413 234 567', '0414 345 678', '0415 456 789',
            '02 9012 3456', '02 9123 4567', '0416 567 890', '0417 678 901', '0418 789 012',
            '02 8234 5678', '02 8345 6789', '0419 890 123', '0412 901 234', '0413 012 345'
        ];

        foreach ($serviceDefs as $sIdx => $svc) {
            $service = $svc['name'];
            $catId = $categories[$service]->id;
            for ($i = 0; $i < 6; $i++) {
                $accId++;
                $prefix = $brandPrefixes[($sIdx + $i) % count($brandPrefixes)];
                $suffix = $brandSuffixes[$service][($sIdx + $i) % count($brandSuffixes[$service])];
                $biz = trim("$prefix $suffix");
                $postcode = $i < 3 ? '2000' : '3000';
                $rate = (string)($baseRateByService[$service] + (($i * 5) % 15));
                $about = $aboutByService[$service];

                // Get contact person name and create business email
                $nameIndex = ($accId - 101) % count($contactNames);
                $contactPerson = $contactNames[$nameIndex][0] . ' ' . $contactNames[$nameIndex][1];
                $businessEmail = 'contact@' . strtolower(str_replace(' ', '', $biz)) . '.com.au';
                $phoneNumber = $phoneNumbers[($accId - 101) % count($phoneNumbers)];

                $profiles[] = [
                    'account_id' => $accId,
                    'business_name' => $biz,
                    'about' => $about,
                    'postcode' => $postcode,
                    'base_rate' => $rate,
                    'email' => $businessEmail,
                    'phone_number' => $phoneNumber,
                    'contact_person' => $contactPerson,
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
                    'email'         => $p['email'],
                    'phone_number'  => $p['phone_number'],
                    'contact_person' => $p['contact_person'],
                ]
            );
            $profile->categories()->sync([$p['category_id']]);
        }
    }
}
