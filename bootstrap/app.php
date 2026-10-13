<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('superadmin*')) {
                return route('superadmin.login');
            }
            if ($request->route('store_slug')) {
                return route('store.backend.login', ['store_slug' => $request->route('store_slug')]);
            }
            return '/backend/login';
        });

        $middleware->alias([
            'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'store.scope' => \App\Http\Middleware\StoreScopeMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
