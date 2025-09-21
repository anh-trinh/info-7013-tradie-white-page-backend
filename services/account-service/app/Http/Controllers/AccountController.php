<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\RabbitMQService;

class AccountController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:accounts',
            'password' => 'required|string',
            'role' => 'required|in:resident,tradie',
        ]);

        $user = new User();
        $user->fill($request->all());
        $user->password = Hash::make($request->input('password'));
        $user->save();

        // Publish account_registered event
        (new RabbitMQService())->publishEvent('account_registered', [
            'email' => $user->email,
            'first_name' => $user->first_name,
        ]);

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['email', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return response()->json(Auth::user());
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $user->fill($request->only(['first_name','last_name','phone_number']));
        $user->save();
        return response()->json($user);
    }

    public function getAllAccounts(Request $request)
    {
        $query = User::query();
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }
        return response()->json($query->get());
    }

    public function getAccountById($id)
    {
        return response()->json(User::findOrFail($id));
    }

    public function updateAccountStatus($id, Request $request)
    {
        $this->validate($request, [
            'status' => 'required|in:active,suspended'
        ]);
        $user = User::findOrFail($id);
        $user->status = $request->input('status');
        $user->save();
        return response()->json($user);
    }

    public function deleteAccount($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Account deleted successfully'], 200);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => Auth::user()
        ]);
    }
}
