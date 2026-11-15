<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UnidadInventarioController extends Controller
{
    use UsaApiPrivada;

    public function edit(string $codigo, string $id): View|RedirectResponse
    {
        $unidad = $this->consultarApi('GET', $this->endpoint($codigo).'/'.rawurlencode($id));
        $ficha = $this->consultarApi('GET', $this->endpoint($codigo));

        return view('inventario.editar-unidad', [...$ficha, 'unidad' => $unidad]);
    }

    public function update(Request $request, string $codigo, string $id): RedirectResponse
    {
        $datos = $request->validate([
            'codigoUnico' => ['required', 'string', 'max:50'], 'codAlmacen' => ['required', 'string', 'max:20'],
            'estadoFisico' => ['required', 'string', 'max:20'], 'calificacion' => ['nullable', 'integer', 'between:1,10'],
            'fechaAlta' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'requiereArreglo' => ['sometimes', 'boolean'], 'requiereCambio' => ['sometimes', 'boolean'], 'requiereBaja' => ['sometimes', 'boolean'],
        ]);
        foreach (['requiereArreglo', 'requiereCambio', 'requiereBaja'] as $campo) {
            $datos[$campo] = $request->boolean($campo);
        }
        $datos['calificacion'] = isset($datos['calificacion']) ? (int) $datos['calificacion'] : null;
        try {
            $this->consultarApi('PUT', $this->endpoint($codigo).'/'.rawurlencode($id), $datos);

            return redirect()->route('inventario.unidades', ['codigo' => $codigo])->with('exito', 'Condición y detalles de la unidad actualizados.');
        } catch (ConnectionException|RequestException $e) {
            report($e);

            return back()->withInput()->withErrors(['api' => 'No pudimos confirmar los cambios. Consulta la unidad antes de volver a enviarlos.']);
        }
    }

    private function endpoint(string $codigo): string
    {
        return '/api/inventario/productos/'.rawurlencode($codigo).'/unidades';
    }

    public function index(string $codigo): Response
    {
        try {
            $ficha = $this->consultarApi('GET', $this->endpoint($codigo));

            if (! is_array($ficha) || ! is_array($ficha['producto'] ?? null)
                || ! is_array($ficha['unidades'] ?? null) || ! is_array($ficha['almacenes'] ?? null)) {
                throw new \UnexpectedValueException('La API no devolvió una ficha de unidades válida.');
            }

            return response()->view('inventario.unidades', [...$ficha, 'errorApi' => null]);
        } catch (ConnectionException|RequestException|\UnexpectedValueException $e) {
            report($e);

            return response()->view('inventario.unidades', [
                'producto' => null,
                'unidades' => [],
                'almacenes' => [],
                'errorApi' => 'No pudimos consultar las unidades. Comprueba que Quarkus y MySQL estén funcionando e inténtalo nuevamente.',
            ], 503);
        }
    }

    public function store(Request $request, string $codigo): RedirectResponse
    {
        $datos = $request->validate([
            'codigoUnico' => ['required', 'string', 'max:50'],
            'codAlmacen' => ['required', 'string', 'max:20'],
            'estadoFisico' => ['required', 'string', 'max:20'],
            'calificacion' => ['nullable', 'integer', 'between:1,10'],
            'fechaAlta' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'requiereArreglo' => ['sometimes', 'boolean'],
            'requiereCambio' => ['sometimes', 'boolean'],
            'requiereBaja' => ['sometimes', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach (['requiereArreglo', 'requiereCambio', 'requiereBaja'] as $campo) {
            $datos[$campo] = $request->boolean($campo);
        }
        $datos['calificacion'] = isset($datos['calificacion']) ? (int) $datos['calificacion'] : null;

        try {
            $this->consultarApi('POST', $this->endpoint($codigo), $datos);

            return redirect()->route('inventario.unidades', ['codigo' => $codigo])
                ->with('exito', 'Unidad registrada: '.$datos['codigoUnico'].'.');
        } catch (ConnectionException|RequestException $e) {
            report($e);

            return back()->withInput()->withErrors([
                'api' => 'No pudimos confirmar el registro. Consulta la lista de unidades antes de volver a enviarlo.',
            ]);
        }
    }
}
