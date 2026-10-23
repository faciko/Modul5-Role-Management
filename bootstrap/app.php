<?php

use App\Http\Middleware\ForceJsonForAjax;
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
        // Redirect untuk tamu yang mencoba membuka halaman auth.
        $middleware->redirectGuestsTo(fn () => route('login'));
        // Redirect untuk user login yang mencoba membuka halaman guest.
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        // Request AJAX/JSON yang ditolak Gate dapat 403 JSON, bukan halaman HTML.
        $middleware->web(append: [
            ForceJsonForAjax::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
