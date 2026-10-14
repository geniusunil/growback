
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AuthenticateUserOrGuest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Register custom middleware alias
        $middleware->alias([
            'user-or-guest' => AuthenticateUserOrGuest::class,
        ]);

        // Keep your existing CSRF configuration
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();