<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$role): Response
    {
        dd($role);  
        if ($role != 'expert-sale') {
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
