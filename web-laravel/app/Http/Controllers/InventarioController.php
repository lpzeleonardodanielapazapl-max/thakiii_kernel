<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InventarioController extends Controller
{
    private function productos(): array
    {
        // Si la API falla, no mostramos el error como inventario vacío.
        return Http::baseUrl(config('services.quarkus.url'))
            ->acceptJson()
            ->timeout(15)
            ->get('/api/inventario/productos')
            ->throw()
            ->json();
    }

    private function buscarProducto(string $codigo): array
    {
        $producto = collect($this->productos())
            ->firstWhere('codigo', $codigo);

        abort_if($producto === null, 404, 'Producto no encontrado.');

        return $producto;
    }

    public function index()
    {
        return view('inventario.index', [
            'productos' => $this->productos(),
        ]);
    }

    public function analizar(string $codigo)
    {
        return view('io.index', [
            'producto' => $this->buscarProducto($codigo),
        ]);
    }

    public function calcular(Request $request, string $codigo)
    {
        $producto = $this->buscarProducto($codigo);

        $datos = $request->validate([
            'demandaAnual' => ['required', 'numeric', 'gt:0'],
            'costoPedido' => ['required', 'numeric', 'gt:0'],
            'costoMantenimientoAnual' => ['required', 'numeric', 'gt:0'],
            'costoUnitario' => ['required', 'numeric', 'min:0'],
            'plazoEntregaDias' => ['required', 'numeric', 'min:0'],
            'diasAnuales' => ['required', 'numeric', 'gt:0'],
        ]);

        $datos = array_map(fn ($valor) => (float) $valor, $datos);

        try {
            $respuesta = Http::baseUrl(config('services.quarkus.url'))
                ->acceptJson()
                ->timeout(15)
                ->post('/api/io/eoq', $datos)
                ->throw();

            return view('io.index', [
                'producto' => $producto,
                'datos' => $datos,
                'resultado' => $respuesta->json(),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->withInput()->withErrors([
                'api' => 'No se pudo conectar con Quarkus.',
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return back()->withInput()->withErrors([
                'api' => 'No se pudo calcular. Revisa la consola de Quarkus.',
            ]);
        }
    }


    public function abc()
{
    $respuesta = Http::baseUrl(config('services.quarkus.url'))
        ->acceptJson()
        ->timeout(15)
        ->get('/api/abc')
        ->throw();

    return view('inventario.abc', [
        'abc' => $respuesta->json(),
    ]);
}

public function crearProducto()
{
    $catalogos = Http::baseUrl(config('services.quarkus.url'))
        ->acceptJson()
        ->timeout(15)
        ->get('/api/registro-productos/catalogos')
        ->throw()
        ->json();

    return view('inventario.crear', [
        'catalogos' => $catalogos,
    ]);
}

public function guardarProducto(Request $request)
{
    $datos = $request->validate([
        'nombre' => ['required', 'string', 'max:200'],
        'codClase' => ['required', 'string', 'max:20'],
        'codMarca' => ['nullable', 'string', 'max:20'],
        'codUm' => ['required', 'string', 'max:10'],
        'modelo' => ['nullable', 'string', 'max:100'],
        'color' => ['nullable', 'string', 'max:80'],
    ]);

    try {
        $respuesta = Http::baseUrl(config('services.quarkus.url'))
            ->acceptJson()
            ->timeout(15)
            ->post('/api/registro-productos', $datos);

        if ($respuesta->status() === 422) {
            return back()
                ->withInput()
                ->withErrors([
                    'api' => $respuesta->json('mensaje')
                        ?? 'Revisa los datos del producto.',
                ]);
        }

        $respuesta->throw();

        return redirect()
            ->route('inventario.crear')
            ->with(
                'exito',
                'Producto registrado. Código: '
                . $respuesta->json('codigo')
                . '. El siguiente paso es registrar sus unidades físicas.'
            );

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        report($e);

        return back()
            ->withInput()
            ->withErrors([
                'api' => 'No hubo respuesta de Quarkus. '
                    . 'Comprueba el inventario antes de reenviar el formulario.',
            ]);

    } catch (\Illuminate\Http\Client\RequestException $e) {
        report($e);

        return back()
            ->withInput()
            ->withErrors([
                'api' => 'La API no pudo completar el registro. '
                    . 'Revisa la consola de Quarkus.',
            ]);
    }
}
}