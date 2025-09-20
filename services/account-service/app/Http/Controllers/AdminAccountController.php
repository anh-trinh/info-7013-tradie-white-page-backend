<?php
namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class AdminAccountController extends Controller
{
    public function list(Request $request)
    {
        $query = Account::query();
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        $accounts = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json($accounts);
    }

    public function get($id)
    {
        $account = Account::findOrFail($id);
        return response()->json($account);
    }

    public function updateStatus(Request $request, $id)
    {
        $this->validate($request, [
            'status' => 'required|in:active,suspended'
        ]);
        $account = Account::findOrFail($id);
        $account->status = $request->status;
        $account->save();
        return response()->json($account);
    }

    public function delete($id)
    {
        $account = Account::findOrFail($id);
        $account->delete();
        return response()->json(['deleted' => true]);
    }
}
