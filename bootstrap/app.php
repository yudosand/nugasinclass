<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['elearning_password', 'elearning_password_confirmation']);
        $middleware->prependToGroup('web', \App\Http\Middleware\SecureWeb::class);
        $middleware->appendToGroup('web', \Illuminate\Session\Middleware\AuthenticateSession::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['elearning_password', 'elearning_password_confirmation', 'elearning_login', 'account_number']);
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            return $response;
        });
    })->create();
