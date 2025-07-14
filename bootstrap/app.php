<?php

use App\Mail\ExceptionOccured;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
        if(app()->environment('production')) {
            $exceptions->reportable(static function (Throwable $exception) {
                try {
                    $content['message'] = $exception->getMessage();

                    $content['file'] = $exception->getFile();

                    $content['line'] = $exception->getLine();

                    $content['trace'] = $exception->getTrace();

                    $content['url'] = request()->url();

                    $content['body'] = request()->all();

                    $content['ip'] = request()->ip();


                    Mail::to('mahmudsheikh25@gmail.com')->send(new ExceptionOccured($content));



                } catch (Throwable $exception) {
                    Log::error('custom',[$exception]);

                }
            });
        }

        info('', ['exception'=>$exceptions]);
    })->create();
