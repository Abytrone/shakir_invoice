<?php

use App\Mail\ExceptionOccured;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '/api/payments/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(static function (Throwable $exception) {
            if (app()->isProduction()) {
                try {

                    Mail::to('mahmudsheikh25@gmail.com')->send(new ExceptionOccured($exception));

                } catch (Throwable $exception) {
                    Log::error('custom', [$exception]);

                }
            }
        });
    })->create();
