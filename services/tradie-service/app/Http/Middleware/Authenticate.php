<?php

namespace App\Http\Middleware;

use Closure;

class Authenticate
{
    public function handle($request, Closure $next)
    {
        // Accept user context forwarded by API Gateway
        $userId = $request->header('X-User-Id');
        if (!$userId) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
