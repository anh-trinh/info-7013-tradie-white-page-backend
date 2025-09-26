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

        // Ensure an admin account exists for administrative access
        // Create or update admin with proper bcrypt hash
        $adminHash = password_hash('admin', PASSWORD_BCRYPT);
        DB::table('accounts')->updateOrInsert(
            ['email' => 'admin@admin.com'],
            [
                'first_name'   => 'Admin',
                'last_name'    => 'User',
                'email'        => 'admin@admin.com',
                'password'     => $adminHash,
                'phone_number' => null,
                'role'         => 'admin',
                'status'       => 'active',
                'updated_at'   => now(),
            ]
        );

        // Ensure a resident test account exists for frontend login
        DB::table('accounts')->updateOrInsert(
            ['email' => 'anhtrinh@resident.com'],
            [
                'first_name' => 'Anh',
                'last_name'  => 'Trinh',
                'email'      => 'anhtrinh@resident.com',
                'password'   => password_hash('secret', PASSWORD_BCRYPT),
                'phone_number' => '0400009999',
                'role'       => 'resident',
                'status'     => 'active',
            ]
        );

        // Create 30 deterministic tradie accounts with fixed IDs 101..130 for cross-service linking
        $password = password_hash('Password123!', PASSWORD_BCRYPT);
        $services = ['Plumbing','Electrical','Carpentry','Painting','Landscaping','Roofing'];

        // Realistic business name data to match tradie service
        $brandPrefixes = ['Apex', 'Harbour', 'Prime', 'Metro', 'Skyline', 'GreenLeaf', 'Rapid', 'Summit', 'Elite', 'Northern'];
        $brandSuffixes = [
            'Plumbing'    => ['Plumbing', 'Pipeworks', 'Flow', 'Drainage'],
            'Electrical'  => ['Electrical', 'Electrics', 'Power', 'Wiring'],
            'Carpentry'   => ['Carpentry', 'Joinery', 'Woodworks', 'Timber'],
            'Painting'    => ['Painting', 'Painters', 'Coatings', 'Decor'],
            'Landscaping' => ['Landscaping', 'Gardens', 'Outdoor', 'Horticulture'],
            'Roofing'     => ['Roofing', 'RoofCare', 'Gutters', 'Tiles'],
        ];

        // Realistic Australian phone numbers (Sydney/NSW area codes)
        $phoneAreas = ['02', '02', '0412', '0413', '0414', '0415', '0416', '0417', '0418', '0419'];
        $phoneNumbers = [
            '02 9876 5432', '02 9234 5678', '0412 345 678', '0413 456 789', '0414 567 890',
            '02 9345 6789', '02 9456 7890', '0415 678 901', '0416 789 012', '0417 890 123',
            '02 9567 8901', '02 9678 9012', '0418 901 234', '0419 012 345', '0412 123 456',
            '02 9789 0123', '02 9890 1234', '0413 234 567', '0414 345 678', '0415 456 789',
            '02 9012 3456', '02 9123 4567', '0416 567 890', '0417 678 901', '0418 789 012',
            '02 8234 5678', '02 8345 6789', '0419 890 123', '0412 901 234', '0413 012 345'
        ];

        // Professional first names for tradies
        $professionalNames = [
            ['Michael', 'Johnson'], ['David', 'Williams'], ['James', 'Brown'], ['Robert', 'Jones'], ['Mark', 'Davis'],
            ['Andrew', 'Miller'], ['Paul', 'Wilson'], ['Steve', 'Moore'], ['Chris', 'Taylor'], ['Matt', 'Anderson'],
            ['Tony', 'Thomas'], ['Daniel', 'Jackson'], ['Peter', 'White'], ['Scott', 'Harris'], ['Ryan', 'Martin'],
            ['Ben', 'Thompson'], ['Luke', 'Garcia'], ['Adam', 'Martinez'], ['Sam', 'Robinson'], ['Tom', 'Clark'],
            ['Nick', 'Rodriguez'], ['Josh', 'Lewis'], ['Jake', 'Lee'], ['Alex', 'Walker'], ['Sean', 'Hall'],
            ['Brad', 'Allen'], ['Kyle', 'Young'], ['Dean', 'King'], ['Joel', 'Wright'], ['Carl', 'Lopez']
        ];

        $i = 0;
        for ($svc = 0; $svc < count($services); $svc++) {
            for ($t = 1; $t <= 5; $t++) {
                $i++;
                $id = 100 + $i; // 101..130
                $service = $services[$svc];

                // Generate realistic business name
                $prefix = $brandPrefixes[($svc + $t - 1) % count($brandPrefixes)];
                $suffix = $brandSuffixes[$service][($svc + $t - 1) % count($brandSuffixes[$service])];
                $businessName = trim("$prefix $suffix");

                // Use professional names
                $nameIndex = ($i - 1) % count($professionalNames);
                $first = $professionalNames[$nameIndex][0];
                $last = $professionalNames[$nameIndex][1];

                // Create business email based on business name
                $emailDomain = strtolower(str_replace(' ', '', $businessName)) . '.com.au';
                $businessEmail = 'contact@' . $emailDomain;

                $row = [
                    'id'           => $id,
                    'first_name'   => $first,
                    'last_name'    => $last,
                    'email'        => $businessEmail,
                    'password'     => $password,
                    'phone_number' => $phoneNumbers[$i - 1],
                    'role'         => 'tradie',
                    'status'       => 'active',
                ];
                DB::table('accounts')->updateOrInsert(['id' => $id], $row);
            }
        }
    }
}
