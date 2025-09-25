<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\RabbitMQService;

class AccountController extends Controller
{
    /**
     * Ensure JSON payloads are merged into the request input for Lumen validation
     */
    private function mergeJsonBody(Request $request): void
    {
        $contentType = $request->headers->get('Content-Type', '');
        $raw = $request->getContent();
        $looksJson = is_string($raw) && strlen($raw) > 0 && (str_starts_with(trim($raw), '{') || str_starts_with(trim($raw), '['));

        if (stripos($contentType, 'application/json') !== false || $looksJson) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge($decoded);
            }
        }
    }
    public function register(Request $request)
    {
        $this->mergeJsonBody($request);
        // Normalize possible phone field aliases from clients (e.g., phone, phoneNumber)
        $payload = $request->all();
        if (!isset($payload['phone_number'])) {
            if (isset($payload['phoneNumber']) && !empty($payload['phoneNumber'])) {
                $request->merge(['phone_number' => $payload['phoneNumber']]);
            } elseif (isset($payload['phone']) && !empty($payload['phone'])) {
                $request->merge(['phone_number' => $payload['phone']]);
            }
        }
        $this->validate($request, [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:accounts',
            'password' => 'required|string',
            'role' => 'required|in:resident,tradie',
            'phone_number' => 'nullable|string',
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

        // Return created user for immediate client verification (non-breaking: adds field)
        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'status' => $user->status,
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $this->mergeJsonBody($request);
        // Extract credentials whether sent as form-data or raw JSON
        $payload = $request->all();
        if (empty($payload)) {
            $decoded = json_decode($request->getContent() ?? '', true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $validator = Validator::make($payload, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = [
            'email' => $payload['email'] ?? null,
            'password' => $payload['password'] ?? null,
        ];

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

    public function getAccountByIdInternal($id)
    {
        // Internal endpoint for service-to-service communication
        // Only return necessary fields for service integration
        $user = User::findOrFail($id);
        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'status' => $user->status
        ]);
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
