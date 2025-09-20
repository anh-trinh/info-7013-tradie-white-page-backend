<?php
namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class AccountController extends Controller
{
    public function register(Request $request)
    {
        \Log::info('Register endpoint hit', $request->all());
        file_put_contents(base_path('storage/logs/lumen.log'), date('Y-m-d H:i:s') . " Register endpoint hit: " . json_encode($request->all()) . "\n", FILE_APPEND);
        try {
            $this->validate($request, [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email|unique:accounts',
                'password' => 'required|min:6',
                'phone_number' => 'required',
                'role' => 'required|in:resident,tradie,admin'
            ]);
            $account = Account::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'role' => $request->role,
                'status' => 'active'
            ]);
            return response()->json($account, 201);
        } catch (\Exception $e) {
            \Log::error('Register error: ' . $e->getMessage(), ['exception' => $e]);
            file_put_contents(base_path('storage/logs/lumen.log'), date('Y-m-d H:i:s') . " Register error: " . $e->getMessage() . "\n", FILE_APPEND);
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $account = Account::where('email', $request->email)->first();
        if (!$account || !Hash::check($request->password, $account->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        $token = JwtService::encode([
            'sub' => $account->id,
            'role' => $account->role,
            'email' => $account->email
        ]);
        return response()->json(['token' => $token]);
    }

    public function me(Request $request)
    {
        $payload = $request->attributes->get('jwt');
        $account = Account::find($payload['sub']);
        return response()->json($account);
    }

    public function updateMe(Request $request)
    {
        $payload = $request->attributes->get('jwt');
        $account = Account::find($payload['sub']);
        $this->validate($request, [
            'first_name' => 'sometimes|required',
            'last_name' => 'sometimes|required',
            'phone_number' => 'sometimes|required'
        ]);
        $account->update($request->only(['first_name', 'last_name', 'phone_number']));
        return response()->json($account);
    }
}
