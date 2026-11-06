<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ProductoController extends Controller
{
    private function api(): PendingRequest
    {
        return Http::baseUrl(config('services.quarkus.url'))->withToken((string) session('api_token', ''))->acceptJson()->timeout(15);
    }

    private function producto(string $codigo): array
    {
        $respuesta = $this->api()->get('/api/inventario/productos/'.rawurlencode($codigo));
        abort_if($respuesta->status() === 404, 404, 'Producto no encontrado.');

        return $respuesta->throw()->json();
    }

    private function catalogos(): array
    {
        return $this->api()->get('/api/registro-productos/catalogos')->throw()->json();
    }

    public function show(string $codigo): View
    {
        return view('inventario.producto', ['producto' => $this->producto($codigo), 'catalogos' => $this->catalogos()]);
    }

    public function edit(string $codigo): View
    {
        return view('inventario.crear', ['producto' => $this->producto($codigo), 'catalogos' => $this->catalogos(), 'editing' => true]);
    }

    public function update(Request $request, string $codigo): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'], 'codClase' => ['required', 'string', 'max:20'],
            'codMarca' => ['nullable', 'string', 'max:20'], 'codUm' => ['required', 'string', 'max:10'],
            'modelo' => ['nullable', 'string', 'max:100'], 'color' => ['nullable', 'string', 'max:80'],
            'stockMinimo' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'estado' => ['required', 'in:ACTIVO,INACTIVO'],
        ]);
        try {
            $respuesta = $this->api()->put('/api/inventario/productos/'.rawurlencode($codigo), $datos);
            abort_if($respuesta->status() === 404, 404, 'Producto no encontrado.');
            if (in_array($respuesta->status(), [400, 409, 422], true)) {
                return back()->withInput()->withErrors(['api' => $respuesta->json('mensaje') ?? 'Revisa los datos del producto.']);
            }
            $respuesta->throw();

            return redirect()->route('inventario.producto', ['codigo' => $codigo])->with('exito', 'Producto actualizado. Sus unidades e historial se conservan.');
        } catch (ConnectionException|RequestException $e) {
            report($e);

            return back()->withInput()->withErrors(['api' => 'No pudimos confirmar los cambios. Consulta el producto antes de volver a enviarlos.']);
        }
    }
}
