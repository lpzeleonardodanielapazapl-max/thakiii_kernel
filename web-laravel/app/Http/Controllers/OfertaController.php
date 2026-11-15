<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class OfertaController extends Controller
{
    public function alojamientos(): View|RedirectResponse
    {
        if (in_array(session('usuario.rol'), ['ADMIN', 'ADMINISTRATIVO'], true)) {
            return redirect()->route('hoteles.index');
        }
        $datos = $this->consultar('/api/publico/hospedaje');

        return view('publico.oferta', [
            'modo' => 'hotel',
            'hoteles' => $datos['error'] ? [] : $datos['cuerpo'],
            'servicios' => [],
            'vehiculos' => [],
            'errorCatalogo' => $datos['error'],
            'enPortal' => session('usuario.rol') === 'CLIENTE',
        ]);
    }

    public function transporte(): View|RedirectResponse
    {
        if (in_array(session('usuario.rol'), ['ADMIN', 'ADMINISTRATIVO'], true)) {
            return redirect()->route('servicios-transporte.index');
        }
        $datos = $this->consultar('/api/publico/transporte');
        $cuerpo = $datos['error'] ? [] : $datos['cuerpo'];

        return view('publico.oferta', [
            'modo' => 'transporte',
            'hoteles' => [],
            'servicios' => $cuerpo['servicios'] ?? [],
            'vehiculos' => $cuerpo['vehiculos'] ?? [],
            'errorCatalogo' => $datos['error'],
            'enPortal' => session('usuario.rol') === 'CLIENTE',
        ]);
    }

    public function elegirHotel(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'hotel' => ['required', 'string', 'max:20'],
            'habitacion' => ['required', 'string', 'max:20'],
        ]);
        if ($rechazo = $this->rechazoPersonal()) {
            return $rechazo;
        }
        $catalogo = $this->consultar('/api/publico/hospedaje');
        if ($catalogo['error']) {
            return back()->withErrors(['oferta' => 'No pudimos consultar los alojamientos. Inténtalo nuevamente.']);
        }
        $hotel = collect($catalogo['cuerpo'])->firstWhere('codigo', $datos['hotel']);
        $habitacion = collect($hotel['habitaciones'] ?? [])->firstWhere('codigo', $datos['habitacion']);
        if (! $hotel || ! $habitacion) {
            return back()->withErrors(['oferta' => 'Ese alojamiento ya no está disponible. Elige otra opción.']);
        }
        $ciudad = ($hotel['ciudad'] ?? '') !== '' ? ', '.$hotel['ciudad'] : '';
        $desayuno = (int) ($habitacion['desayuno'] ?? 0) === 1 ? ' · desayuno' : '';
        $etiqueta = $this->recortar($hotel['nombre'].$ciudad.' · '.$habitacion['nombre'].' · '.$this->monto($habitacion['precioNoche']).' '.$habitacion['moneda'].'/noche'.$desayuno);

        return $this->guardar([
            'hotel' => $etiqueta,
            'hotelCodigo' => $hotel['codigo'],
            'habitacionCodigo' => $habitacion['codigo'],
        ], 'Alojamiento guardado. Quedará en tu solicitud de viaje.');
    }

    public function elegirTransporte(Request $request): RedirectResponse
    {
        $datos = $request->validate(['servicio' => ['required', 'string', 'max:20']]);
        if ($rechazo = $this->rechazoPersonal()) {
            return $rechazo;
        }
        $catalogo = $this->consultar('/api/publico/transporte');
        if ($catalogo['error']) {
            return back()->withErrors(['oferta' => 'No pudimos consultar los traslados. Inténtalo nuevamente.']);
        }
        $servicio = collect($catalogo['cuerpo']['servicios'] ?? [])->firstWhere('codigo', $datos['servicio']);
        if (! $servicio) {
            return back()->withErrors(['oferta' => 'Ese traslado ya no está disponible. Elige otra opción.']);
        }
        $ruta = ($servicio['origen'] ?? '') !== '' ? ' · '.$servicio['origen'].' → '.$servicio['destino'] : '';
        $tipo = ($servicio['tipo'] ?? '') !== '' ? ' · '.$servicio['tipo'] : '';
        $etiqueta = $this->recortar($servicio['nombre'].$ruta.$tipo.' · '.$this->monto($servicio['precio']).' '.$servicio['moneda']);

        return $this->guardar([
            'transporte' => $etiqueta,
            'transporteCodigo' => $servicio['codigo'],
        ], 'Traslado guardado. Quedará en tu solicitud de viaje.');
    }

    public function quitar(Request $request): RedirectResponse
    {
        if ($rechazo = $this->rechazoPersonal()) {
            return $rechazo;
        }
        $tipo = $request->validate(['tipo' => ['required', 'in:hotel,transporte']])['tipo'];
        $borrador = $request->session()->get('viaje_borrador', []);
        if ($tipo === 'hotel') {
            unset($borrador['hotel'], $borrador['hotelCodigo'], $borrador['habitacionCodigo']);
        } else {
            unset($borrador['transporte'], $borrador['transporteCodigo']);
        }
        $request->session()->put('viaje_borrador', $borrador);

        return back()->with('exito', 'Quitamos esa elección. Puedes elegir otra o escribir una preferencia.');
    }

    /**
     * @param  array<string, string>  $cambios
     */
    private function guardar(array $cambios, string $mensaje): RedirectResponse
    {
        session(['viaje_borrador' => [...session('viaje_borrador', []), ...$cambios]]);
        if (session('usuario.rol') === 'CLIENTE') {
            return redirect()->route('portal.solicitud')->with('exito', $mensaje);
        }

        return redirect()->route('acceso.registro')->with('exito', $mensaje.' Crea tu cuenta o ingresa para enviarla.');
    }

    private function rechazoPersonal(): ?RedirectResponse
    {
        if (in_array(session('usuario.rol'), ['ADMIN', 'ADMINISTRATIVO'], true)) {
            return back()->withErrors(['oferta' => 'Para elegir alojamiento o traslado, ingresa con una cuenta de cliente.']);
        }

        return null;
    }

    /**
     * @return array{error: bool, cuerpo: array<mixed>}
     */
    private function consultar(string $path): array
    {
        try {
            $response = Http::baseUrl(config('services.quarkus.url'))->acceptJson()->connectTimeout(2)->timeout(8)->get($path);
            if ($response->successful()) {
                return ['error' => false, 'cuerpo' => $response->json() ?? []];
            }
        } catch (ConnectionException $e) {
            report($e);
        }

        return ['error' => true, 'cuerpo' => []];
    }

    private function monto(mixed $precio): string
    {
        if (! is_numeric($precio)) {
            return (string) $precio;
        }
        $numero = (float) $precio;

        return floor($numero) == $numero ? (string) (int) $numero : rtrim(rtrim(number_format($numero, 2, '.', ''), '0'), '.');
    }

    private function recortar(string $texto): string
    {
        return mb_strlen($texto) > 200 ? mb_substr($texto, 0, 200) : $texto;
    }
}
