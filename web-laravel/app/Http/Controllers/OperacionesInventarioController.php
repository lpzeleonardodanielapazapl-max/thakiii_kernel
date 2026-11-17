<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperacionesInventarioController extends Controller
{
    use UsaApiPrivada;

    public function catalogos(Request $request): View
    {
        $tipo = $request->query('tipo', 'almacenes');
        abort_unless(in_array($tipo, ['almacenes', 'clases', 'marcas', 'medidas'], true), 404);
        $catalogos = $this->consultarApi('GET', '/api/inventario/catalogos');
        $editar = $request->filled('editar') ? collect($catalogos[$tipo])->firstWhere('codigo', $request->query('editar')) : null;
        abort_if($request->filled('editar') && ! $editar, 404);

        return view('inventario.catalogos', compact('catalogos', 'tipo', 'editar'));
    }

    public function guardarCatalogo(Request $request, string $tipo, ?string $codigo = null): RedirectResponse
    {
        abort_unless(in_array($tipo, ['almacenes', 'clases', 'marcas', 'medidas'], true), 404);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'], 'ubicacion' => ['nullable', 'string', 'max:200'],
            'estado' => ['nullable', 'in:ACTIVO,INACTIVO'], 'categoria' => ['nullable', 'in:ALPINISMO,COCINA,EVENTOS'],
            'serializado' => ['sometimes', 'boolean'], 'consumible' => ['sometimes', 'boolean'],
            'pais' => ['nullable', 'string', 'max:80'], 'abreviatura' => ['nullable', 'string', 'max:10'],
        ]);
        $this->consultarApi($codigo ? 'PUT' : 'POST', '/api/inventario/catalogos/'.$tipo.($codigo ? '/'.rawurlencode($codigo) : ''), $datos);

        return redirect()->route('inventario.catalogos', ['tipo' => $tipo])->with('exito', 'Catálogo actualizado.');
    }

    public function index(Request $request): View
    {
        $seccion = $request->query('ver', 'existencias');
        abort_unless(in_array($seccion, ['existencias', 'referencias', 'documentos', 'movimientos', 'prestamos', 'precios'], true), 404);
        $categoria = $request->validate(['categoria' => ['nullable', 'in:TODAS,ALPINISMO,COCINA,EVENTOS']])['categoria'] ?? 'TODAS';
        $datos = $this->consultarApi('GET', '/api/inventario/operaciones');
        if ($categoria !== 'TODAS' && $seccion === 'existencias') {
            foreach (['unidades', 'stock'] as $lista) {
                $datos[$lista] = array_values(array_filter($datos[$lista], fn (array $r): bool => $r['categoria'] === $categoria));
            }
        }

        return view('inventario.operaciones', [...$datos, 'seccion' => $seccion, 'categoria' => $categoria]);
    }

    public function documento(string $codigo): View
    {
        $documento = $this->consultarApi('GET', '/api/inventario/operaciones/documentos/'.rawurlencode($codigo));
        if ($documento['tipo'] === 'REF') {
            try {
                $fuente = json_decode($documento['observaciones'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
                foreach ($documento['items'] as &$item) {
                    $item['fuente'] = json_decode($item['observaciones'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
                }
                unset($item);
            } catch (\JsonException) {
                abort(422, 'La referencia del documento tiene observaciones inválidas.');
            }

            return view('inventario.referencia', compact('documento', 'fuente'));
        }

        return view('inventario.documento', compact('documento'));
    }
}
