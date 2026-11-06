<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    use UsaApiPrivada;

    public function inicio(): View
    {
        return view('portal.inicio', [...$this->consultarApi('GET', '/api/cliente/resumen'), 'mensajes' => $this->consultarApi('GET', '/api/cliente/mensajes')]);
    }

    public function perfil(): View
    {
        return view('portal.perfil', ['perfil' => $this->consultarApi('GET', '/api/cliente/perfil')]);
    }

    public function solicitud(): View|RedirectResponse
    {
        $perfil = $this->consultarApi('GET', '/api/cliente/perfil');
        if (! ($perfil['completitud']['completo'] ?? false)) {
            session()->put('continuar_solicitud', true);

            return redirect()->route('portal.perfil')->withErrors(['perfil' => 'Completa tu perfil al 100 % para continuar con tu pedido de viaje. Tus preferencias se conservan.']);
        }

        return view('portal.solicitud', ['borrador' => session('viaje_borrador', [])]);
    }

    public function mensajes(): View
    {
        return view('portal.mensajes', ['mensajes' => $this->consultarApi('GET', '/api/cliente/mensajes'), ...$this->consultarApi('GET', '/api/cliente/resumen')]);
    }

    public function notificaciones(): View
    {
        return view('portal.notificaciones', ['notificaciones' => $this->consultarApi('GET', '/api/cliente/notificaciones')]);
    }

    public function propuesta(string $codigo): View
    {
        return view('portal.propuesta', ['propuesta' => $this->consultarApi('GET', '/api/cliente/propuestas/'.rawurlencode($codigo))]);
    }

    public function guardarPerfil(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombres' => ['required', 'string', 'max:100'], 'apellidos' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'], 'direccion' => ['nullable', 'string', 'max:200'],
            'tipoDocumento' => ['nullable', 'string', 'max:20'], 'numeroDocumento' => ['nullable', 'string', 'max:30'],
            'fechaNacimiento' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'], 'genero' => ['nullable', 'string', 'max:20'],
        ]);
        $perfil = $this->consultarApi('PUT', '/api/cliente/perfil', $datos);
        if (($perfil['completitud']['completo'] ?? false) && $request->session()->pull('continuar_solicitud', false)) {
            return redirect()->route('portal.solicitud')->with('exito', 'Tu perfil está completo. Continúa con las preferencias de tu viaje.');
        }

        return back()->with('exito', 'Tu perfil está actualizado.');
    }

    public function solicitar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fechaInicio' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fechaFin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fechaInicio'],
            'personas' => ['required', 'integer', 'min:1', 'max:1000'],
            'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'moneda' => ['required', 'in:BOB,USD,EUR'], 'destinos' => ['nullable', 'string', 'max:200'],
            'intereses' => ['required', 'string', 'max:4000'], 'hotel' => ['nullable', 'string', 'max:200'],
            'alimentacion' => ['nullable', 'string', 'max:200'], 'transporte' => ['nullable', 'string', 'max:200'],
        ]);
        $datos['personas'] = (int) $datos['personas'];
        $perfil = $this->consultarApi('GET', '/api/cliente/perfil');
        if (! ($perfil['completitud']['completo'] ?? false)) {
            $request->session()->put('viaje_borrador', $datos);
            $request->session()->put('continuar_solicitud', true);

            return redirect()->route('portal.perfil')->withErrors(['perfil' => 'Completa tu perfil al 100 % antes de enviar tu solicitud. Conservamos las preferencias de tu viaje.']);
        }
        $this->consultarApi('POST', '/api/cliente/solicitudes', $datos);
        $request->session()->forget(['viaje_borrador', 'continuar_solicitud']);

        return redirect()->route('portal.inicio')->with('exito', 'Recibimos tus preferencias. El asesor preparará tu propuesta de viaje.');
    }

    public function enviar(Request $request): RedirectResponse
    {
        $datos = $request->validate(['contenido' => ['required', 'string', 'max:4000']]);
        $this->consultarApi('POST', '/api/cliente/mensajes', $datos);

        return back()->with('exito', 'Mensaje enviado a tu asesor.');
    }

    public function asignar(): RedirectResponse
    {
        $asesor = $this->consultarApi('POST', '/api/cliente/asignacion');

        return back()->with('exito', $asesor ? 'Tu asesor está asignado.' : 'Aún no hay asesores disponibles. Puedes registrar tu solicitud mientras tanto.');
    }

    public function aceptar(string $codigo): RedirectResponse
    {
        $this->consultarApi('POST', '/api/cliente/propuestas/'.rawurlencode($codigo).'/aceptar');

        return redirect()->route('portal.mensajes')->with('exito', 'Comunicamos tu aceptación. El administrativo debe registrarla antes del anticipo.');
    }

    public function cancelar(Request $request, string $codigo): RedirectResponse
    {
        $datos = $request->validate(['contenido' => ['required', 'string', 'max:2000']]);
        $this->consultarApi('POST', '/api/cliente/reservas/'.rawurlencode($codigo).'/cancelacion', $datos);

        return redirect()->route('portal.mensajes')->with('exito', 'Solicitud de cancelación enviada al asesor.');
    }

    public function leer(string $id): RedirectResponse
    {
        $this->consultarApi('POST', '/api/cliente/notificaciones/'.rawurlencode($id).'/leer');

        return back()->with('exito', 'Notificación marcada como leída.');
    }
}
