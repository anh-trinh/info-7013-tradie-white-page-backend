<?php

namespace App\Http\Middleware;

use Closure;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        $role = $request->header('X-User-Role');
        if ($role !== 'admin') {
            return response('Forbidden.', 403);
        }
        return $next($request);
    }
}
