<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PublicoController extends Controller
{
    public function inicio(): View
    {
        $datos = ['empresa' => [], 'destinos' => [], 'testimonios' => []];
        $errorCatalogo = false;
        try {
            $response = Http::baseUrl(config('services.quarkus.url'))->acceptJson()->connectTimeout(2)->timeout(6)->get('/api/publico/inicio');
            if ($response->successful()) {
                $datos = [...$datos, ...$response->json()];
            } else {
                $errorCatalogo = true;
            }
        } catch (ConnectionException $e) {
            $errorCatalogo = true;
        }

        return view('publico.inicio', [...$datos, 'errorCatalogo' => $errorCatalogo]);
    }

    public function inspiracion(string $slug): View
    {
        $destino = config('publico.inspiraciones.'.$slug);
        abort_unless(is_array($destino), 404);

        return view('publico.destino', ['destino' => $destino, 'editorial' => true]);
    }

    public function destino(string $codigo): View
    {
        $response = Http::baseUrl(config('services.quarkus.url'))->acceptJson()->timeout(10)->get('/api/publico/destinos/'.rawurlencode($codigo));
        abort_if($response->status() === 404, 404);

        return view('publico.destino', ['destino' => $response->throw()->json(), 'editorial' => false]);
    }
}
