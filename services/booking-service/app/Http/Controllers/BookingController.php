<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\Booking;
use App\Models\QuoteMessage;

class BookingController extends Controller
{
    public function createQuote(Request $request)
    {
        $this->validate($request, [
            'tradie_account_id' => 'required|integer',
            'job_description' => 'required|string',
            'service_address' => 'required|string'
        ]);

        $quote = Quote::create([
            'resident_account_id' => auth()->user()->id,
            'tradie_account_id' => $request->input('tradie_account_id'),
            'job_description' => $request->input('job_description'),
            'service_address' => $request->input('service_address'),
            'service_postcode' => $request->input('service_postcode') ?? null,
            'status' => 'pending'
        ]);

        return response()->json($quote, 201);
    }

    public function listQuotes()
    {
        $userId = auth()->user()->id;
        $quotes = Quote::where('resident_account_id',$userId)
            ->orWhere('tradie_account_id',$userId)
            ->with('messages')
            ->get();
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
            'sender_account_id' => auth()->user()->id,
            'message' => $request->input('message'),
            'offered_price' => $request->input('offered_price')
        ]);

        return response()->json(['quote'=>$quote,'message'=>$msg],200);
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

        $booking = Booking::create([
            'quote_id' => $quote->id,
            'final_price' => $request->input('final_price'),
            'scheduled_at' => $request->input('scheduled_at'),
            'status' => 'scheduled'
        ]);

        return response()->json($booking, 201);
    }

    public function listBookings()
    {
        $userId = auth()->user()->id;
        $bookings = Booking::whereHas('quote', function($q) use ($userId) {
            $q->where('resident_account_id',$userId)
              ->orWhere('tradie_account_id',$userId);
        })->with('quote')->get();
        return response()->json($bookings);
    }

    public function updateStatus($id, Request $request)
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