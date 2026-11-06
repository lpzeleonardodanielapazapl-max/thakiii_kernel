<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AccesoController extends Controller
{
    private function inicializable(Request $request): bool
    {
        if (! app()->environment('local') || ! in_array($request->ip(), ['127.0.0.1', '::1'], true)) {
            return false;
        }
        try {
            return Http::baseUrl(config('services.quarkus.url'))->acceptJson()->timeout(5)->get('/api/acceso/configuracion')->json('configurarAdministrador') === true;
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function ingresar(Request $request): View
    {
        return view('acceso.form', ['modo' => 'ingresar', 'inicializable' => $this->inicializable($request)]);
    }

    public function registro(): View
    {
        return view('acceso.form', ['modo' => 'registrar', 'inicializable' => false]);
    }

    public function configurar(Request $request): View
    {
        abort_unless($this->inicializable($request), 404);

        return view('acceso.form', ['modo' => 'inicializar', 'inicializable' => true]);
    }

    public function autenticar(Request $request): RedirectResponse
    {
        return $this->enviar($request, 'ingresar');
    }

    public function registrar(Request $request): RedirectResponse
    {
        return $this->enviar($request, 'registrar');
    }

    public function inicializar(Request $request): RedirectResponse
    {
        abort_unless($this->inicializable($request), 404);

        return $this->enviar($request, 'inicializar');
    }

    private function enviar(Request $request, string $modo): RedirectResponse
    {
        $rules = ['email' => ['required', 'email', 'max:150'], 'password' => ['required', 'string', 'max:255']];
        if ($modo !== 'ingresar') {
            $rules['nombres'] = ['required', 'string', 'max:'.($modo === 'inicializar' ? '60' : '100')];
            $rules['password'] = ['required', 'string', 'min:8', 'max:255', 'confirmed'];
        }
        if ($modo === 'registrar') {
            $rules['apellidos'] = ['required', 'string', 'max:100'];
        }
        $datos = $request->validate($rules);
        try {
            $response = Http::baseUrl(config('services.quarkus.url'))->acceptJson()->timeout(20)->post('/api/acceso/'.$modo, $datos);
            if ($response->failed()) {
                return back()->withInput($request->only('email', 'nombres', 'apellidos'))->withErrors(['acceso' => $response->json('mensaje') ?? 'No se pudo completar el acceso. Revisa tus datos.']);
            }
            $sesion = $response->json();
            $request->session()->regenerate();
            $request->session()->put(['api_token' => $sesion['token'], 'usuario' => $sesion['usuario']]);

            return redirect()->route($sesion['usuario']['rol'] === 'CLIENTE'
                ? ($request->session()->has('viaje_borrador') ? 'portal.solicitud' : 'portal.inicio') : 'admin.index');
        } catch (ConnectionException $e) {
            return back()->withInput($request->only('email', 'nombres', 'apellidos'))->withErrors(['acceso' => 'No pudimos conectar con el servicio. Inténtalo nuevamente.']);
        }
    }

    public function salir(Request $request): RedirectResponse
    {
        if ($request->session()->has('api_token')) {
            try {
                Http::baseUrl(config('services.quarkus.url'))->withToken($request->session()->get('api_token'))->timeout(5)->post('/api/acceso/salir');
            } catch (ConnectionException $e) {
                report($e);
            }
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('publico.inicio');
    }
}
