<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
class JWTOptional
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // اگر توکن وجود داشت → authenticate شود
            if ($token = JWTAuth::getToken()) {
                JWTAuth::authenticate($token);
            }
        } catch (\Exception $e) {
            // توکن نیست یا نامعتبر است → کاربر ناشناس می‌ماند
        }

        return $next($request);
    }
}
