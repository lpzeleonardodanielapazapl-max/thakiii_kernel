<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ConnectionException|RequestException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'No se pudo consultar el servicio.'], 503);
            }

            if (! $request->isMethod('GET')) {
                return back()->withInput()->withErrors(['api' => 'No pudimos completar la consulta. Inténtalo nuevamente.']);
            }

            return response()->view('errors.api', [], 503);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
