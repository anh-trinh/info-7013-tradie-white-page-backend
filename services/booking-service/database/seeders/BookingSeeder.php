<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quote;
use App\Models\Booking;
use App\Models\QuoteMessage;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // Clear old data to avoid orphaned messages sticking to new quote IDs
        QuoteMessage::truncate();
        Quote::truncate();
        Booking::truncate();

        for ($i = 1; $i <= 10; $i++) {
            $q = Quote::create([
                'resident_account_id' => rand(1, 5),
                'tradie_account_id'   => rand(1, 5),
                'service_address'     => '1 Test Street',
                'service_postcode'    => (string)rand(2000, 2999),
                'job_description'     => 'Fix leaking pipe',
                'status'              => 'pending',
            ]);
            Booking::create([
                'quote_id'    => $q->id,
                'final_price' => rand(80, 300),
                'scheduled_at'=> date('Y-m-d\TH:i:s', time() + rand(1,7)*86400),
                'status'      => 'scheduled',
            ]);
        }
    }
}
