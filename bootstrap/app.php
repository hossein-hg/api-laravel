<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Models\Log;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {  // فقط برای API requests (با Accept: application/json)
                return response()->json([
                    'data' => null,
                    'statusCode' => 401,
                    'success' => false,
                    'message' => 'توکن نامعتبر یا منقضی شده است.',
                    'errors' => null
                ], 401);
            }
        });

        $exceptions->report(function (Throwable $e) {
            // ذخیره در DB
            Log::create([
                'level' => 'error',
                'message' => $e->getMessage(),
                'context' => ['code' => $e->getCode()], 
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id() ?? null,
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ?? null,
                'url' => request()->fullUrl() ?? null,
            ]);
        });
    })->create();
