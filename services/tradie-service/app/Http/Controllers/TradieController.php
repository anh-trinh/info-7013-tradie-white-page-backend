<?php
namespace App\Http\Controllers;

use App\Models\TradieProfile;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class TradieController extends Controller
{
    public function index(Request $request)
    {
        $query = TradieProfile::query();
        if ($request->has('service')) {
            $query->whereHas('services', function ($q) use ($request) {
                $q->where('service_category_id', $request->service);
            });
        }
        if ($request->has('location')) {
            $query->where('postcode', $request->location);
        }
        $tradies = $query->paginate(20);
        return response()->json($tradies);
    }

    public function show($id)
    {
        $tradie = TradieProfile::with('services.category')->findOrFail($id);
        return response()->json($tradie);
    }
}
