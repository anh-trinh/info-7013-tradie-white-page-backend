<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\Booking;
use App\Models\QuoteMessage;
use App\Services\RabbitMQService;
use App\Services\AccountClient;
use Carbon\Carbon;

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

        return response()->json($quote, 201);
    }

    public function getQuotes(Request $request)
    {
        $userId = $request->header('X-User-Id', 1);
        $quotes = Quote::where('resident_account_id',$userId)
            ->orWhere('tradie_account_id',$userId)
            ->with('messages')
            ->get();
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

    public function acceptQuote($id)
    {
        $quote = Quote::findOrFail($id);
        $quote->status = 'accepted';
        $quote->save();
        return response()->json($quote);
    }

    public function createBooking(Request $request)
    {
        $this->validate($request, [
            'quote_id' => 'required|integer',
            'final_price' => 'required|numeric',
            'scheduled_at' => 'required|date'
        ]);

        $quote = Quote::findOrFail($request->input('quote_id'));
        if ($quote->status !== 'accepted') {
            return response()->json(['message'=>'Quote must be accepted before creating booking'], 400);
        }

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

        (new RabbitMQService())->publishEvent('booking_created', [
            'booking_id' => $booking->id,
        ]);
        return response()->json($booking, 201);
    }

    public function getJobs(Request $request)
    {
        $userId = $request->header('X-User-Id', 1);
        $bookings = Booking::whereHas('quote', function($q) use ($userId) {
            $q->where('resident_account_id',$userId)
              ->orWhere('tradie_account_id',$userId);
        })->with('quote')->get();
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
