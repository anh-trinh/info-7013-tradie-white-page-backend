<?php
namespace App\Http\Middleware;

use Closure;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JwtMiddleware
{
    public function handle($request, Closure $next, $role = null)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }
        try {
            $payload = (array) JwtService::decode($token);
            if ($role && (!isset($payload['role']) || $payload['role'] !== $role)) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            $request->attributes->add(['jwt' => $payload]);
        } catch (\Exception $e) {
            Log::error('JWT error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid token'], 401);
        }
        return $next($request);
    }
}
