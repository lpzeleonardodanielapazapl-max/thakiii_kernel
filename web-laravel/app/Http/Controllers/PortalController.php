<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $codigosPais = ['+33', '+32', '+41', '+352', '+377', '+34', '+39', '+49', '+31', '+351', '+44', '+591', '+51', '+56', '+54', '+57', '+593', '+595', '+598', '+55', '+52', '+1'];
        $datos = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'codigoPais' => ['required', Rule::in($codigosPais)],
            'telefonoNumero' => ['required', 'regex:/^\d{6,15}$/'],
            'direccion' => ['required', 'string', 'min:5', 'max:200', 'regex:/^[\p{L}\p{N}\s.,#\-\/°ºª]+$/u'],
            'tipoDocumento' => ['required', Rule::in(['Pasaporte', 'Carte d\'identité', 'Titre de séjour', 'DNI', 'NIE', 'CI'])],
            'numeroDocumento' => ['required', 'string', 'max:30'],
            'fechaNacimiento' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'genero' => ['nullable', 'in:Femenino,Masculino'],
        ], [
            'codigoPais.required' => 'Selecciona el código de país del teléfono.',
            'telefonoNumero.required' => 'Ingresa tu número de teléfono.',
            'telefonoNumero.regex' => 'El teléfono debe tener entre 6 y 15 dígitos, sin espacios ni signos.',
            'direccion.required' => 'Ingresa tu dirección.',
            'direccion.regex' => 'La dirección solo admite letras, números, espacios y los signos . , # - / °.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
        ]);
        $datos['telefono'] = $datos['codigoPais'].' '.$datos['telefonoNumero'];
        unset($datos['codigoPais'], $datos['telefonoNumero']);
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
            'hotelCodigo' => ['nullable', 'string', 'max:20'], 'habitacionCodigo' => ['nullable', 'string', 'max:20'],
            'transporteCodigo' => ['nullable', 'string', 'max:20'],
            'tipoHotel' => ['nullable', Rule::in(config('publico.tiposHotel'))],
            'tipoTransporte' => ['nullable', Rule::in(config('publico.tiposTransporte'))],
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

    public function guardarBorrador(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'catalogo' => ['required', 'in:hotel,transporte'],
            'fechaInicio' => ['nullable', 'date_format:Y-m-d'], 'fechaFin' => ['nullable', 'date_format:Y-m-d'],
            'personas' => ['nullable', 'integer', 'min:1', 'max:1000'], 'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'moneda' => ['nullable', 'in:BOB,USD,EUR'], 'destinos' => ['nullable', 'string', 'max:200'], 'intereses' => ['nullable', 'string', 'max:4000'],
            'hotel' => ['nullable', 'string', 'max:200'], 'alimentacion' => ['nullable', 'string', 'max:200'], 'transporte' => ['nullable', 'string', 'max:200'],
            'hotelCodigo' => ['nullable', 'string', 'max:20'], 'habitacionCodigo' => ['nullable', 'string', 'max:20'], 'transporteCodigo' => ['nullable', 'string', 'max:20'],
            'tipoHotel' => ['nullable', Rule::in(config('publico.tiposHotel'))], 'tipoTransporte' => ['nullable', Rule::in(config('publico.tiposTransporte'))],
        ]);
        $ruta = $datos['catalogo'] === 'hotel' ? 'publico.alojamientos' : 'publico.transporte';
        unset($datos['catalogo']);
        $request->session()->put('viaje_borrador', [...$request->session()->get('viaje_borrador', []), ...$datos]);

        return redirect()->route($ruta);
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

    public function pago(string $codigo): View|RedirectResponse
    {
        $resumen = $this->consultarApi('GET', '/api/cliente/resumen');
        $reserva = collect($resumen['reservas'] ?? [])->firstWhere('codigo', $codigo);
        if (! $reserva) {
            return redirect()->route('portal.inicio')->withErrors(['pago' => 'No encontramos esa reserva.']);
        }
        if (! in_array($reserva['estado'], ['PENDIENTE_ANTICIPO', 'CONFIRMADA'], true)) {
            return redirect()->route('portal.inicio')->withErrors(['pago' => 'Esta reserva no admite pagos en línea.']);
        }

        return view('portal.pago', ['reserva' => $reserva]);
    }

    public function pagar(Request $request, string $codigo): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:ANTICIPO,TOTAL'],
            'titular' => ['required', 'string', 'max:120'],
            'metodo' => ['nullable', 'string', 'max:50'],
            'tarjeta' => ['required', 'regex:/^\d{13,19}$/'],
            'vencimiento' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv' => ['required', 'regex:/^\d{3,4}$/'],
        ], [
            'tarjeta.regex' => 'Ingresa un número de tarjeta válido (solo dígitos).',
            'vencimiento.regex' => 'Usa el formato MM/AA.',
            'cvv.regex' => 'El CVV debe tener 3 o 4 dígitos.',
        ]);
        $this->consultarApi('POST', '/api/cliente/reservas/'.rawurlencode($codigo).'/pago-online', [
            'tipo' => $datos['tipo'],
            'titular' => $datos['titular'],
            'metodo' => $datos['metodo'] ?: 'Tarjeta',
            'referencia' => 'ONLINE-'.substr(hash('sha256', $datos['tarjeta'].$datos['vencimiento'].now()->timestamp), 0, 16),
        ]);

        return redirect()->route('portal.inicio')->with('exito', $datos['tipo'] === 'TOTAL'
            ? 'Pago total registrado. Tu viaje queda al día.'
            : 'Anticipo registrado. Si cubre el 50 %, tu viaje queda confirmado.');
    }
}
