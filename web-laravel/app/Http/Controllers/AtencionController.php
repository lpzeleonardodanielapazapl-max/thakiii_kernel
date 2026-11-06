<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AtencionController extends Controller
{
    use UsaApiPrivada;

    public function index(): View
    {
        return view('atencion.index', $this->consultarApi('GET', '/api/atencion/panel'));
    }

    public function calendario(Request $request): View
    {
        $datos = $request->validate(['mes' => ['nullable', 'date_format:Y-m', 'after_or_equal:2020-01', 'before_or_equal:2100-12'], 'dia' => ['nullable', 'date_format:Y-m-d']]);
        $mes = Carbon::createFromFormat('!Y-m', $datos['mes'] ?? now('America/La_Paz')->format('Y-m'), 'America/La_Paz');
        $desde = $mes->copy()->startOfWeek(Carbon::MONDAY);
        $hasta = $mes->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $viajes = $this->consultarApi('GET', '/api/atencion/calendario', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]);
        $diaSeleccionado = $datos['dia'] ?? null;
        if ($diaSeleccionado && ($diaSeleccionado < $desde->toDateString() || $diaSeleccionado > $hasta->toDateString())) {
            $diaSeleccionado = null;
        }
        $dias = [];
        for ($fecha = $desde->copy(); $fecha->lte($hasta); $fecha->addDay()) {
            $dias[] = ['fecha' => $fecha->copy(), 'viajes' => array_values(array_filter($viajes, fn (array $viaje): bool => $viaje['fechaInicio'] <= $fecha->toDateString() && $viaje['fechaFin'] >= $fecha->toDateString()))];
        }
        $agenda = array_values(array_filter($viajes, fn (array $viaje): bool => $diaSeleccionado
            ? $viaje['fechaInicio'] <= $diaSeleccionado && $viaje['fechaFin'] >= $diaSeleccionado
            : $viaje['fechaInicio'] <= $mes->copy()->endOfMonth()->toDateString() && $viaje['fechaFin'] >= $mes->toDateString()));

        return view('atencion.calendario', compact('mes', 'dias', 'agenda', 'diaSeleccionado'));
    }

    public function conversacion(string $id): View
    {
        return view('atencion.conversacion', ['conversacion' => $this->consultarApi('GET', '/api/atencion/conversaciones/'.rawurlencode($id))]);
    }

    public function solicitud(Request $request, string $codigo): View
    {
        $solicitud = $this->consultarApi('GET', '/api/atencion/solicitudes/'.rawurlencode($codigo));
        $propuestas = collect($solicitud['propuestas'])->sortByDesc('version');
        $base = $propuestas->first();
        if ($request->filled('editar')) {
            $base = $propuestas->firstWhere('codigo', $request->query('editar'));
            abort_unless($base, 404, 'La propuesta no pertenece a esta solicitud.');
        }

        return view('atencion.solicitud', [
            'solicitud' => $solicitud,
            'propuestaBase' => $base,
            'siguienteVersion' => ((int) $propuestas->max('version')) + 1,
        ]);
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
            'dias' => ['required', 'array', 'min:1', 'max:120'],
            'dias.*' => ['required', 'array:descripcion,servicios'],
            'dias.*.descripcion' => ['required', 'string', 'max:4000'],
            'dias.*.servicios' => ['nullable', 'string', 'max:2000'],
        ], [
            'dias.required' => 'Completa al menos un día del itinerario.',
            'dias.max' => 'El itinerario admite hasta 120 días.',
            'dias.*.descripcion.required' => 'Describe las actividades de este día.',
            'dias.*.descripcion.max' => 'La descripción del día admite hasta 4000 caracteres.',
            'dias.*.servicios.max' => 'Los servicios del día admiten hasta 2000 caracteres.',
        ]);
        $dias = array_values($datos['dias']);
        $datos['dias'] = array_map(fn (array $dia, int $index): array => ['dia' => $index + 1, 'descripcion' => $dia['descripcion'], 'servicios' => $dia['servicios'] ?? null], $dias, array_keys($dias));
        $propuesta = $this->consultarApi('POST', '/api/atencion/propuestas', [...$datos, 'solicitud' => $codigo]);

        return redirect()->route('atencion.solicitud', $codigo)->with('exito', isset($propuesta['version'])
            ? 'Propuesta '.$propuesta['version'].' enviada al cliente. Las versiones anteriores se conservan en el historial.'
            : 'Propuesta enviada al espacio del cliente.');
    }

    public function aceptar(Request $request, string $codigo): RedirectResponse
    {
        $request->validate(['confirmar' => ['accepted']]);
        $this->consultarApi('POST', '/api/atencion/propuestas/'.rawurlencode($codigo).'/aceptacion');

        return back()->with('exito', 'Aceptación registrada. La reserva queda pendiente del anticipo del 50 %.');
    }
}
