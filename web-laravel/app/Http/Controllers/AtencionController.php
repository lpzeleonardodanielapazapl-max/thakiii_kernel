<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AtencionController extends Controller
{
    use UsaApiPrivada;

    public function index(): View
    {
        return view('atencion.index', $this->consultarApi('GET', '/api/atencion/panel'));
    }

    public function conversacion(string $id): View
    {
        return view('atencion.conversacion', ['conversacion' => $this->consultarApi('GET', '/api/atencion/conversaciones/'.rawurlencode($id))]);
    }

    public function solicitud(string $codigo): View
    {
        return view('atencion.solicitud', ['solicitud' => $this->consultarApi('GET', '/api/atencion/solicitudes/'.rawurlencode($codigo))]);
    }

    public function reserva(string $codigo): View
    {
        return view('atencion.reserva', ['reserva' => $this->consultarApi('GET', '/api/atencion/reservas/'.rawurlencode($codigo))]);
    }

    public function pago(Request $request, string $codigo): RedirectResponse
    {
        $datos = $request->validate(['fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'monto' => ['required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'], 'metodo' => ['required', 'string', 'max:50'], 'referencia' => ['required', 'string', 'max:100'], 'verificado' => ['accepted']]);
        $datos['verificado'] = true;
        $this->consultarApi('POST', '/api/atencion/reservas/'.rawurlencode($codigo).'/pagos', $datos);

        return back()->with('exito', 'Pago externo verificado y registrado. El cliente verá el estado actualizado en su espacio.');
    }

    public function cancelar(Request $request, string $codigo): RedirectResponse
    {
        $request->validate(['confirmar' => ['accepted']]);
        $this->consultarApi('POST', '/api/atencion/reservas/'.rawurlencode($codigo).'/cancelacion');

        return back()->with('exito', 'Cancelación solicitada por el cliente registrada. Se conservan los pagos y el historial.');
    }

    public function responder(Request $request, string $id): RedirectResponse
    {
        $datos = $request->validate(['contenido' => ['required', 'string', 'max:4000']]);
        $this->consultarApi('POST', '/api/atencion/conversaciones/'.rawurlencode($id).'/mensajes', $datos);

        return back()->with('exito', 'Respuesta enviada al cliente.');
    }

    public function proponer(Request $request, string $codigo): RedirectResponse
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:200'], 'descripcion' => ['required', 'string', 'max:4000'],
            'precio' => ['required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'], 'moneda' => ['required', 'in:BOB,USD,EUR'],
            'itinerario' => ['required', 'string', 'max:20000'], 'servicios' => ['nullable', 'string', 'max:2000'],
        ]);
        $dias = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $datos['itinerario']))));
        $datos['dias'] = array_map(fn (string $texto, int $index): array => ['dia' => $index + 1, 'descripcion' => $texto, 'servicios' => ($datos['servicios'] ?? null) ?: null], $dias, array_keys($dias));
        unset($datos['itinerario'], $datos['servicios']);
        $this->consultarApi('POST', '/api/atencion/propuestas', [...$datos, 'solicitud' => $codigo]);

        return back()->with('exito', 'Propuesta enviada al espacio del cliente.');
    }

    public function aceptar(Request $request, string $codigo): RedirectResponse
    {
        $request->validate(['confirmar' => ['accepted']]);
        $this->consultarApi('POST', '/api/atencion/propuestas/'.rawurlencode($codigo).'/aceptacion');

        return back()->with('exito', 'Aceptación registrada. La reserva queda pendiente del anticipo del 50 %.');
    }
}
