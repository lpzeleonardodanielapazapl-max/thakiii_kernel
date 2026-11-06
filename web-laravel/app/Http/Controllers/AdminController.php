<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function index()
    {
        $resumen = null;
        $error = null;

        try {
            $productos = Http::baseUrl(config('services.quarkus.url'))->withToken((string) session('api_token', ''))
                ->acceptJson()
                ->timeout(15)
                ->get('/api/inventario/productos')
                ->throw()
                ->json();

            if (! is_array($productos) || ! array_is_list($productos)) {
                throw new \UnexpectedValueException(
                    'La API no devolvió una lista de productos.'
                );
            }

            $coleccion = collect($productos);

            $resumen = [
                'productos' => $coleccion->count(),
                'unidades' => $coleccion->sum('unidadesRegistradas'),
                'arreglo' => $coleccion->sum('unidadesRequierenArreglo'),
                'bajas' => $coleccion->sum('unidadesConBaja'),
            ];
        } catch (
            ConnectionException|
            RequestException|
            \UnexpectedValueException $e
        ) {
            report($e);

            $error = 'No pudimos consultar el inventario. '
                .'Comprueba que Quarkus y MySQL estén funcionando.';
        }

        return view('admin.index', compact('resumen', 'error'));
    }
}
