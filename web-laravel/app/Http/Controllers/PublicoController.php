<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PublicoController extends Controller
{
    public function planificar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'destinos' => ['required', 'string', 'max:200'],
            'fechaInicio' => ['nullable', 'required_with:fechaFin', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fechaFin' => ['nullable', 'required_with:fechaInicio', 'date_format:Y-m-d', 'after_or_equal:fechaInicio'],
            'personas' => ['required', 'integer', 'min:1', 'max:1000'],
            'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'moneda' => ['required', 'in:BOB,USD,EUR'],
            'hotel' => ['nullable', 'string', 'max:200'], 'alimentacion' => ['nullable', 'string', 'max:200'], 'transporte' => ['nullable', 'string', 'max:200'],
        ], [
            'destinos.required' => 'Cuéntanos qué destino te gustaría conocer.',
            'fechaInicio.required_with' => 'Indica la fecha de inicio del viaje.',
            'fechaFin.required_with' => 'Indica la fecha de regreso.',
            'fechaInicio.after_or_equal' => 'La fecha de inicio debe ser hoy o posterior.',
            'fechaFin.after_or_equal' => 'La fecha de regreso debe ser igual o posterior a la de inicio.',
            'personas.min' => 'El viaje debe tener al menos un viajero.',
            'personas.max' => 'Indica una cantidad de viajeros entre 1 y 1000.',
        ]);
        if (in_array($request->session()->get('usuario.rol'), ['ADMIN', 'ADMINISTRATIVO'], true)) {
            return redirect()->route('publico.inicio')->withErrors(['viaje' => 'Para planificar tu viaje, ingresa con una cuenta de cliente.']);
        }
        $request->session()->put('viaje_borrador', $datos);
        if ($request->session()->get('usuario.rol') === 'CLIENTE') {
            return redirect()->route('portal.solicitud');
        }

        return redirect()->route('acceso.registro')->with('exito', 'Conservamos tus preferencias. Crea tu cuenta de cliente o ingresa para continuar con tu viaje.');
    }

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
