<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class SesionPrivada
{
    public function handle(Request $request, Closure $next, string $ambito = 'personal'): Response
    {
        $token = $request->session()->get('api_token');
        if (! $token) {
            return redirect()->route('acceso.ingresar')->with('aviso', 'Ingresa para continuar.');
        }
        try {
            $response = Http::baseUrl(config('services.quarkus.url'))->withToken($token)->acceptJson()->timeout(10)->get('/api/acceso/sesion');
        } catch (ConnectionException $e) {
            report($e);

            return response()->view('acceso.error', [], 503);
        }
        if (in_array($response->status(), [401, 403], true)) {
            $request->session()->forget(['api_token', 'usuario']);

            return redirect()->route('acceso.ingresar')->withErrors(['acceso' => 'Tu sesión terminó. Vuelve a ingresar.']);
        }
        if ($response->failed()) {
            return response()->view('acceso.error', [], 503);
        }
        $usuario = $response->json();
        $allowed = $ambito === 'cliente' ? ['CLIENTE'] : ['ADMIN', 'ADMINISTRATIVO'];
        if (! in_array($usuario['rol'] ?? '', $allowed, true)) {
            return response()->view('acceso.denegado', [], 403);
        }
        $request->session()->put('usuario', $usuario);

        return $next($request);
    }
}
