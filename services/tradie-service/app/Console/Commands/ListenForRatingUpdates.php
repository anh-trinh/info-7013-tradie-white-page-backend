<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradieProfile;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Illuminate\Support\Facades\DB;

class ListenForRatingUpdates extends Command
{
    protected $signature = 'ratings:listen';
    protected $description = 'Listen for new reviews and update average ratings';

    public function handle()
    {
        echo " [*] Starting rating update listener...\n";

        // Check if php-amqplib is available
        if (!class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
            echo " [!] Error: php-amqplib/php-amqplib package not found. Please install it with: composer require php-amqplib/php-amqplib\n";
            return 1;
        }

        try {
            $connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'message-broker'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASS', 'guest')
            );

            $channel = $connection->channel();
            $channel->queue_declare('rating_update_queue', false, true, false, false);

            echo " [*] Waiting for rating updates. To exit press CTRL+C\n";

            $callback = function ($msg) {
                try {
                    $data = json_decode($msg->body, true);
                    echo ' [x] Received ', $msg->body, "\n";

                    if (!isset($data['tradie_account_id']) || !isset($data['rating'])) {
                        echo " [!] Invalid message format\n";
                        return;
                    }

                    $profile = TradieProfile::where('account_id', $data['tradie_account_id'])->first();

                    if ($profile) {
                        // Logic tính toán rating trung bình mới
                        $newRating = (float) $data['rating'];
                        $oldAvg = (float) $profile->average_rating;
                        $oldCount = (int) $profile->reviews_count;

                        // Tính rating trung bình mới
                        $newAvg = $oldCount > 0
                            ? (($oldAvg * $oldCount) + $newRating) / ($oldCount + 1)
                            : $newRating;

                        $profile->average_rating = round($newAvg, 1);
                        $profile->reviews_count = $oldCount + 1;
                        $profile->save();

                        echo " [x] Updated rating for tradie account: {$data['tradie_account_id']} - New avg: {$profile->average_rating} ({$profile->reviews_count} reviews)\n";
                    } else {
                        echo " [!] Tradie profile not found for account_id: {$data['tradie_account_id']}\n";
                    }
                } catch (\Exception $e) {
                    echo " [!] Error processing message: " . $e->getMessage() . "\n";
                }
            };

            $channel->basic_consume('rating_update_queue', '', false, true, false, false, $callback);

            while ($channel->is_consuming()) {
                $channel->wait();
            }

            $channel->close();
            $connection->close();

        } catch (\Exception $e) {
            echo " [!] Connection error: " . $e->getMessage() . "\n";
            return 1;
        }

        return 0;
    }
}
