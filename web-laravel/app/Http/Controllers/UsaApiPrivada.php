<?php

namespace App\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

trait UsaApiPrivada
{
    private function consultarApi(string $method, string $path, array $datos = []): array
    {
        $response = Http::baseUrl(config('services.quarkus.url'))->withToken((string) session('api_token'))
            ->acceptJson()->timeout(15)->send($method, $path, $method === 'GET' ? ['query' => $datos] : ['json' => $datos]);
        if ($response->status() === 401) {
            session()->forget(['api_token', 'usuario']);
            throw new HttpResponseException(redirect()->route('acceso.ingresar')->withErrors(['acceso' => 'Tu sesión terminó. Vuelve a ingresar.']));
        }
        abort_if($response->status() === 404, 404);
        abort_if($response->status() === 403, 403, 'No tienes permiso para esta operación.');
        if (in_array($response->status(), [400, 409, 422, 429], true)) {
            throw ValidationException::withMessages(['api' => $response->json('mensaje') ?? 'Revisa los datos e inténtalo nuevamente.']);
        }
        $response->throw();

        return $response->json() ?? [];
    }
}
