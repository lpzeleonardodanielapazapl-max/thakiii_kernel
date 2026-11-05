<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function index()
    {
        $resumen = null;
        $error = null;

        try {
            $productos = Http::baseUrl(config('services.quarkus.url'))
                ->acceptJson()
                ->timeout(15)
                ->get('/api/inventario/productos')
                ->throw()
                ->json();

            if (!is_array($productos) || !array_is_list($productos)) {
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
            \Illuminate\Http\Client\ConnectionException |
            \Illuminate\Http\Client\RequestException |
            \UnexpectedValueException $e
        ) {
            report($e);

            $error = 'No pudimos consultar el inventario. '
                . 'Comprueba que Quarkus y MySQL estén funcionando.';
        }

        return view('admin.index', compact('resumen', 'error'));
    }
}