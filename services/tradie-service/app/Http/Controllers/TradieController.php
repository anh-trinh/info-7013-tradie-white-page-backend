<?php
namespace App\Http\Controllers;

use App\Models\TradieProfile;
use Illuminate\Http\Request;

class TradieController extends Controller
{
    public function search(Request $request)
    {
        $query = TradieProfile::query();
        if ($request->has('location')) {
            $query->where('postcode', 'like', '%'.$request->input('location').'%');
        }
        if ($request->has('service')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->input('service').'%');
            });
        }
        $tradies = $query->with('categories')->get();
        return response()->json($tradies);
    }

    public function getById($id)
    {
        $tradie = TradieProfile::with('categories')->findOrFail($id);
        return response()->json($tradie);
    }
}
