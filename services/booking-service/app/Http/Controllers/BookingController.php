<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\Booking;
use App\Models\QuoteMessage;
use App\Services\RabbitMQService;
use App\Services\AccountClient;
use Carbon\Carbon;
use GuzzleHttp\Client;

class BookingController extends Controller
{
    public function createQuote(Request $request)
    {
        $this->validate($request, [
            'tradie_account_id' => 'required|integer',
            'job_description' => 'required|string',
            'service_address' => 'required|string'
        ]);

        // TODO: integrate proper authentication; using X-User-Id header fallback for now
        $userId = $request->header('X-User-Id', 1);
        $quote = Quote::create([
            'resident_account_id' => $userId,
            'tradie_account_id' => $request->input('tradie_account_id'),
            'job_description' => $request->input('job_description'),
            'service_address' => $request->input('service_address'),
            'service_postcode' => $request->input('service_postcode') ?? null,
            'status' => 'pending'
        ]);

        // Publish real-time event for new quote request (notify tradie)
        try {
            (new RabbitMQService())->publishEvent(
                'new_quote_request',
                ['quote' => $quote],
                'realtime_updates_queue'
            );
        } catch (\Throwable $e) {
            // fail-soft
        }

        return response()->json($quote, 201);
    }

    public function getQuotes(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');

        if (!$userId) {
            return response()->json(['message' => 'User not identified'], 401);
        }

        $query = Quote::query();
        if ($userRole === 'tradie') {
            $query->where('tradie_account_id', $userId);
        } else {
            // default: resident
            $query->where('resident_account_id', $userId);
        }

        $quotes = $query->with('messages')->latest()->get();

        // Enrich with resident name
        $accountClient = new AccountClient();
        $cache = [];
        $quotes = $quotes->map(function($q) use ($accountClient, &$cache) {
            $rid = (int)$q->resident_account_id;
            if (!array_key_exists($rid, $cache)) {
                $cache[$rid] = $accountClient->getAccountMinById($rid);
            }
            $res = $cache[$rid];
            $q->setAttribute('resident_name', $res ? trim(($res['first_name'] ?? '') . ' ' . ($res['last_name'] ?? '')) : null);
            return $q;
        });

        return response()->json($quotes);
    }

    public function respondQuote($id, Request $request)
    {
        $this->validate($request, [
            'message' => 'required|string',
            'offered_price' => 'nullable|numeric'
        ]);

        $quote = Quote::findOrFail($id);
        $quote->status = 'responded';
        $quote->save();

        $msg = QuoteMessage::create([
            'quote_id' => $quote->id,
            'sender_account_id' => $request->header('X-User-Id', 1),
            'message' => $request->input('message'),
            'offered_price' => $request->input('offered_price')
        ]);

        return response()->json(['quote'=>$quote,'message'=>$msg],200);
    }

    public function acceptQuote($id, Request $request)
    {
        $quote = Quote::findOrFail($id);
        $userId = (int) $request->header('X-User-Id', 0);
        // Only resident can accept the quote
        if ($userId !== (int)$quote->resident_account_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $quote->status = 'accepted';
        $quote->save();

        // Publish realtime event for acceptance
        try {
            (new RabbitMQService())->publishEvent(
                'quote_accepted',
                ['quote' => $quote],
                'realtime_updates_queue'
            );
        } catch (\Throwable $e) {
            // fail-soft
        }

        return response()->json($quote);
    }

    // Lấy chi tiết một quote, bao gồm cả lịch sử tin nhắn
    public function getQuoteById(Request $request, $id)
    {
        $quote = Quote::with(['messages' => function($q){ $q->orderBy('created_at','asc'); }])->findOrFail($id);
        // Basic authorization: only resident or tradie of this quote can view
        $userId = (int) $request->header('X-User-Id', 0);
        if ($userId !== (int)$quote->resident_account_id && $userId !== (int)$quote->tradie_account_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        // Enrich resident name for convenience
        $accountClient = new AccountClient();
        $res = $accountClient->getAccountMinById((int)$quote->resident_account_id);
        $quote->setAttribute('resident_name', $res ? trim(($res['first_name'] ?? '') . ' ' . ($res['last_name'] ?? '')) : null);
        return response()->json($quote);
    }

    // Thêm một tin nhắn (và giá đề xuất) vào quote
    public function addQuoteMessage(Request $request, $id)
    {
        $this->validate($request, [
            'message' => 'required|string',
            'offered_price' => 'nullable|numeric|min:0'
        ]);

        $quote = Quote::findOrFail($id);

        // Permission: only two parties in the quote can send messages
        $senderId = (int) $request->header('X-User-Id', 0);
        if ($senderId !== (int)$quote->resident_account_id && $senderId !== (int)$quote->tradie_account_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $message = new QuoteMessage();
        $message->quote_id = $quote->id;
        $message->sender_account_id = $senderId;
        $message->message = $request->input('message');
        if ($request->has('offered_price')) {
            $message->offered_price = $request->input('offered_price');
        }
    $message->save();

        // Update status: if price included, treat as counter-offer; else responded
        if ($request->filled('offered_price')) {
            $quote->status = 'counter-offered';
        } else {
            $quote->status = 'responded';
        }
        $quote->save();

        // Reload quote with messages for realtime broadcast
        $updatedQuote = Quote::with(['messages' => function($q){ $q->orderBy('created_at','asc'); }])->find($id);

        // Publish real-time update event
        try {
            (new RabbitMQService())->publishEvent(
                'new_quote_message',
                ['quote' => $updatedQuote],
                'realtime_updates_queue'
            );
        } catch (\Throwable $e) {
            // fail-soft; don't block API
        }

        return response()->json($message, 201);
    }

    public function createBooking(Request $request)
    {
        $this->validate($request, [
            'quote_id' => 'required|integer|exists:quotes,id|unique:bookings',
            'final_price' => 'required|numeric',
            'scheduled_at' => 'required|date'
        ]);

        $quote = Quote::findOrFail($request->input('quote_id'));
        // Permission: only the resident of this quote can create a booking
        $userId = (int) $request->header('X-User-Id', 0);
        if ($userId !== (int)$quote->resident_account_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Set quote as accepted when creating a booking
        $quote->status = 'accepted';
        $quote->save();

        // Normalize scheduled_at to MySQL DATETIME format
        try {
            $scheduledAt = Carbon::parse($request->input('scheduled_at'))->toDateTimeString();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid scheduled_at datetime'], 422);
        }

        $booking = Booking::create([
            'quote_id' => $quote->id,
            'final_price' => $request->input('final_price'),
            'scheduled_at' => $scheduledAt,
            'status' => 'scheduled'
        ]);

        // Legacy/notification event (kept): booking_created to notifications_queue
        (new RabbitMQService())->publishEvent('booking_created', [ 'booking_id' => $booking->id ]);

        // Realtime event with full context for both parties
        try {
            $bookingFull = Booking::with('quote')->find($booking->id);
            (new RabbitMQService())->publishEvent(
                'booking_created',
                ['booking' => $bookingFull],
                'realtime_updates_queue'
            );
        } catch (\Throwable $e) {
            // fail-soft
        }
        return response()->json($booking, 201);
    }

    public function getJobs(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');
        if (!$userId) return response()->json(['message' => 'User not identified'], 401);

        $query = Booking::query()->with('quote');
        if ($userRole === 'tradie') {
            $query->whereHas('quote', function($q) use ($userId) { $q->where('tradie_account_id', $userId); });
        } else {
            $query->whereHas('quote', function($q) use ($userId) { $q->where('resident_account_id', $userId); });
        }

        $bookings = $query->latest('scheduled_at')->get();

        // Aggregate contact info from Account Service in a single request
        if ($bookings->isNotEmpty()) {
            $accountIds = $bookings->pluck('quote.resident_account_id')
                ->merge($bookings->pluck('quote.tradie_account_id'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($accountIds)) {
                try {
                    // Build base URL from env (defaults to 8000 port as defined in docker-compose)
                    $base = rtrim(getenv('ACCOUNT_SERVICE_URL') ?: 'http://account-service:8000', '/');
                    $client = new Client(['base_uri' => $base]);
                    // Use internal, non-authenticated bulk endpoint for service-to-service call
                    $url = '/api/internal/accounts?ids=' . implode(',', $accountIds);
                    $response = $client->request('GET', $url);
                    $accountsData = json_decode($response->getBody()->getContents(), true);
                    $accountsMap = collect($accountsData)->keyBy('id');

                    // Attach contacts to each booking (as attributes so they're serialized)
                    $bookings->each(function ($booking) use ($accountsMap) {
                        $rid = $booking->quote->resident_account_id ?? null;
                        $tid = $booking->quote->tradie_account_id ?? null;
                        $resident = $rid ? $accountsMap->get((int)$rid) : null;
                        $tradie = $tid ? $accountsMap->get((int)$tid) : null;
                        $booking->setAttribute('resident_contact', $resident);
                        $booking->setAttribute('tradie_contact', $tradie);
                        // Convenience flat fields for FE consumption
                        $booking->setAttribute('tradie_phone', $tradie['phone_number'] ?? null);
                        $booking->setAttribute('tradie_first_name', $tradie['first_name'] ?? null);
                        $booking->setAttribute('tradie_last_name', $tradie['last_name'] ?? null);
                    });
                } catch (\Throwable $e) {
                    // Fail-soft: if aggregation fails, still return base bookings
                }
            }
        }

        return response()->json($bookings);
    }

    public function updateJobStatus($id, Request $request)
    {
        $this->validate($request, [
            'status' => 'required|in:scheduled,in_progress,completed,cancelled'
        ]);

        $booking = Booking::findOrFail($id);
        $booking->status = $request->input('status');
        $booking->save();

        if ($booking->status === 'completed') {
            (new RabbitMQService())->publishEvent('job_completed', [
                'booking_id' => $booking->id,
            ]);
        }

        return response()->json($booking);
    }

    public function getAllJobsForAdmin()
    {
        return response()->json(Booking::with('quote')->latest()->get());
    }

    public function getJobDetailsForAdmin($id)
    {
        $booking = Booking::with(['quote.messages'])->findOrFail($id);
        return response()->json($booking);
    }

    public function updateJobStatusByAdmin(Request $request, $id)
    {
        $this->validate($request, [
            'status' => 'required|in:scheduled,in_progress,completed,cancelled'
        ]);

        $booking = Booking::findOrFail($id);
        $booking->status = $request->input('status');
        $booking->save();

        return response()->json($booking);
    }
}
