<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    public static function decode($token)
    {
        $key = env('JWT_SECRET');
        return JWT::decode($token, new Key($key, 'HS256'));
    }
}
