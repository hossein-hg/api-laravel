<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        
        $user = auth()->user();
        $userRole = $user->getJWTCustomClaims()['role'] ?? null;
        
        
        
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'data' => null,
                'statusCode' => 403,
                'message' => "دسترسی مجاز نیست!",
                'success' => false,
                'errors' => null
            ], 403);
        }
        return $next($request);
    }
}
