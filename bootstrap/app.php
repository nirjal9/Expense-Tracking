<?php

use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckIncomeSet;
use App\Http\Middleware\CheckInitialRegistration;
use App\Http\Middleware\RedirectIfRegistrationComplete;
use App\Http\Middleware\VerifyCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => AdminMiddleware::class, // Register middleware with alias "admin"
            'permission' => PermissionMiddleware::class,
            'check.income' => CheckIncomeSet::class,
            'check.initial.registration' => CheckInitialRegistration::class,
            'redirect.if.initial.registration.complete' => RedirectIfRegistrationComplete::class,
        ]);
        
        // Replace the default CSRF middleware with our custom one
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => VerifyCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
