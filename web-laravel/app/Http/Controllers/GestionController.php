<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class GestionController extends Controller
{
    private function meta(Request $request): array
    {
        $modulo = $request->route('modulo');
        $meta = config('gestion.'.$modulo);
        abort_unless(is_array($meta), 404);
        if (in_array($modulo, ['usuarios', 'roles'], true) && session('usuario.rol') === 'ADMINISTRATIVO') {
            $meta['canCreate'] = false;
            $meta['canEdit'] = false;
            $meta['canDelete'] = false;
        }

        return ['modulo' => $modulo, ...$meta];
    }

    private function api(): PendingRequest
    {
        return Http::baseUrl(config('services.quarkus.url'))->withToken((string) session('api_token', ''))->acceptJson()->timeout(15);
    }

    private function exigir(ClientResponse $respuesta): void
    {
        if ($respuesta->status() === 401) {
            session()->forget(['api_token', 'usuario']);
            throw new HttpResponseException(redirect()->route('acceso.ingresar')->withErrors(['acceso' => 'Tu sesión terminó. Vuelve a ingresar.']));
        }
    }

    private function endpoint(string $modulo, ?string $codigo = null): string
    {
        return '/api/v1/'.$modulo.($codigo === null ? '' : '/'.rawurlencode($codigo));
    }

    private function registro(Request $request, array $meta): array
    {
        $respuesta = $this->api()->get($this->endpoint($meta['modulo'], $request->route('codigo')));
        $this->exigir($respuesta);
        abort_if($respuesta->status() === 404, 404, 'Registro no encontrado.');

        return $respuesta->throw()->json();
    }

    private function catalogos(array $meta): array
    {
        $catalogos = [];
        foreach (array_unique(array_column($meta['fields'], 'catalogo')) as $modulo) {
            $respuesta = $this->api()->get($this->endpoint($modulo));
            $this->exigir($respuesta);
            $lista = $respuesta->throw()->json();
            $contexto = [];
            if (in_array($modulo, ['habitaciones', 'empleados'], true)) {
                $relacion = $modulo === 'habitaciones' ? 'hoteles' : 'personas';
                $relacionRespuesta = $this->api()->get($this->endpoint($relacion));
                $this->exigir($relacionRespuesta);
                $contexto = collect($relacionRespuesta->throw()->json())->keyBy(config('gestion.'.$relacion.'.key'))->all();
            }
            $clave = config('gestion.'.$modulo.'.key');
            foreach ($lista as $registro) {
                $label = $modulo === 'personas'
                    ? trim($registro['nombres'].' '.$registro['apellidos']).' · '.$registro[$clave]
                    : ($registro[config('gestion.'.$modulo.'.labelKey')] ?? $registro[$clave]);
                if ($modulo === 'habitaciones') {
                    $label .= ' · '.($contexto[$registro['codHotel']]['nombre'] ?? $registro['codHotel']);
                }
                if ($modulo === 'empleados') {
                    $persona = $contexto[$registro['codPersona']] ?? [];
                    $label = trim(($persona['nombres'] ?? '').' '.($persona['apellidos'] ?? '')).' · '.$registro[$clave];
                }
                $catalogos[$modulo][$registro[$clave]] = $label;
            }
            $catalogos[$modulo] ??= [];
        }

        return $catalogos;
    }

    public function index(Request $request): View
    {
        $meta = $this->meta($request);
        $respuesta = $this->api()->get($this->endpoint($meta['modulo']));
        $this->exigir($respuesta);
        $registros = $respuesta->throw()->json();
        $catalogos = $this->catalogos($meta);
        foreach ($registros as &$registro) {
            foreach (['codPersona' => ['personas', '_persona'], 'codCargo' => ['cargos', '_cargo'], 'codDep' => ['departamentos', '_departamento'], 'codRol' => ['roles', '_rol'], 'codHotel' => ['hoteles', '_hotel'], 'codTipoHab' => ['habitaciones', '_habitacion'], 'codEmpleado' => ['empleados', '_empleado']] as $campo => [$catalogo, $destino]) {
                $registro[$destino] = $catalogos[$catalogo][$registro[$campo] ?? ''] ?? ($registro[$campo] ?? null);
            }
        }
        unset($registro);

        return view('gestion.index', compact('meta', 'registros'));
    }

    public function create(Request $request): View
    {
        $meta = $this->meta($request);
        abort_unless($meta['canCreate'] ?? true, 405);
        $registro = [];
        if (in_array($meta['modulo'], ['empleados', 'clientes'], true)) {
            $registro['codPersona'] = $request->query('persona');
        }

        return view('gestion.form', ['meta' => $meta, 'registro' => $registro, 'catalogos' => $this->catalogos($meta), 'editing' => false]);
    }

    public function show(Request $request): View
    {
        $meta = $this->meta($request);

        return view('gestion.show', ['meta' => $meta, 'registro' => $this->registro($request, $meta), 'catalogos' => $this->catalogos($meta), 'confirmDelete' => false]);
    }

    public function edit(Request $request): View
    {
        $meta = $this->meta($request);
        abort_unless($meta['canEdit'] ?? true, 403);

        return view('gestion.form', ['meta' => $meta, 'registro' => $this->registro($request, $meta), 'catalogos' => $this->catalogos($meta), 'editing' => true]);
    }

    public function delete(Request $request): View
    {
        $meta = $this->meta($request);
        abort_unless($meta['canDelete'] ?? true, 405);

        return view('gestion.show', ['meta' => $meta, 'registro' => $this->registro($request, $meta), 'catalogos' => $this->catalogos($meta), 'confirmDelete' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->guardar($request, false);
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->guardar($request, true);
    }

    private function guardar(Request $request, bool $editing): RedirectResponse
    {
        $meta = $this->meta($request);
        abort_unless($editing || ($meta['canCreate'] ?? true), 405);
        abort_unless(! $editing || ($meta['canEdit'] ?? true), 403);
        $rules = [];
        $labels = [];
        foreach ($meta['fields'] as $campo => $opciones) {
            $rules[$campo] = $editing ? ($opciones['editRules'] ?? $opciones['rules']) : $opciones['rules'];
            $labels[$campo] = $opciones['label'];
        }
        $datos = $request->validate($rules, [], $labels);
        foreach ($meta['fields'] as $campo => $opciones) {
            if ($opciones['type'] === 'checkbox') {
                $datos[$campo] = $request->boolean($campo);
            }
            if ($opciones['type'] === 'password' && empty($datos[$campo])) {
                unset($datos[$campo]);
            }
        }

        try {
            $respuesta = $this->api()->send($editing ? 'PUT' : 'POST', $this->endpoint($meta['modulo'], $editing ? $request->route('codigo') : null), ['json' => $datos]);
            $this->exigir($respuesta);
            if (in_array($respuesta->status(), [400, 409, 422], true)) {
                return $this->rechazado($request, $respuesta->json('mensaje') ?? $respuesta->json('error') ?? 'Revisa los datos del formulario.');
            }
            abort_if($respuesta->status() === 404, 404, 'Registro no encontrado.');
            $registro = $respuesta->throw()->json();
            $codigo = $registro[$meta['key']];
            if (! $editing && $meta['modulo'] === 'personas' && in_array($request->input('volver'), ['empleados', 'clientes'], true)) {
                return redirect()->route($request->input('volver').'.create', ['persona' => $codigo])->with('exito', 'Persona registrada. Completa ahora su vinculación.');
            }

            return redirect()->route($meta['modulo'].'.show', ['codigo' => $codigo])->with('exito', $editing ? 'Cambios guardados correctamente.' : 'Registro creado correctamente.');
        } catch (ConnectionException|RequestException $e) {
            report($e);

            return $this->rechazado($request, 'No pudimos confirmar la operación. Revisa el listado antes de volver a enviarla.');
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $meta = $this->meta($request);
        abort_unless($meta['canDelete'] ?? true, 405);
        $request->validate(['confirmar' => ['accepted']]);
        try {
            $respuesta = $this->api()->delete($this->endpoint($meta['modulo'], $request->route('codigo')));
            $this->exigir($respuesta);
            if ($respuesta->status() === 409) {
                return $this->rechazado($request, $respuesta->json('mensaje') ?? $respuesta->json('error') ?? 'El registro tiene información vinculada y no puede eliminarse.');
            }
            abort_if($respuesta->status() === 404, 404, 'Registro no encontrado.');
            $respuesta->throw();

            return redirect()->route($meta['modulo'].'.index')->with('exito', 'Registro eliminado correctamente.');
        } catch (ConnectionException|RequestException $e) {
            report($e);

            return $this->rechazado($request, 'No pudimos confirmar la eliminación. Revisa el listado antes de volver a intentarlo.');
        }
    }

    private function rechazado(Request $request, string $mensaje): RedirectResponse
    {
        return back()->withInput($request->except(['password', 'password_confirmation', '_token', '_method']))->withErrors(['api' => $mensaje]);
    }
}
