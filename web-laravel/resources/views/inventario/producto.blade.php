@extends('layouts.admin')
@section('titulo', 'Detalle del producto')
@section('contenido')
    <x-page-header :title="$producto['nombre']" :description="$producto['codigo']" eyebrow="INVENTARIO · FICHA DEL PRODUCTO">
        <a class="boton boton-secundario" href="{{ route('inventario.index') }}"><x-icon name="back"/> Inventario</a>
        <a class="boton" href="{{ route('inventario.editar', ['codigo' => $producto['codigo']]) }}">Editar producto</a>
    </x-page-header>
    @if (session('exito'))<div class="aviso" role="status">{{ session('exito') }}</div>@endif
    <section class="panel"><h2>Datos del equipo</h2><dl class="ficha-grid">
        @foreach (['codClase' => ['clases', 'Clase'], 'codMarca' => ['marcas', 'Marca'], 'codUm' => ['unidadesMedida', 'Unidad de medida']] as $campo => [$lista, $label])
            <div><dt>{{ $label }}</dt><dd>{{ collect($catalogos[$lista])->firstWhere('codigo', $producto[$campo])['nombre'] ?? 'Sin registrar' }}</dd></div>
        @endforeach
        @foreach (['modelo' => 'Modelo', 'color' => 'Color', 'stockMinimo' => 'Stock mínimo de referencia', 'estado' => 'Estado'] as $campo => $label)<div><dt>{{ $label }}</dt><dd>{{ $producto[$campo] ?? '—' }}</dd></div>@endforeach
    </dl></section>
    <div class="acciones-pagina no-imprimir"><a class="boton boton-navy" href="{{ route('inventario.unidades', ['codigo' => $producto['codigo']]) }}">Ver unidades físicas <x-icon name="arrow"/></a>
        @if ($producto['estado'] === 'ACTIVO')<a class="boton boton-secundario" href="{{ route('inventario.analizar', ['codigo' => $producto['codigo']]) }}">Analizar reposición</a>@endif
        <button class="boton boton-secundario" data-print type="button"><x-icon name="print"/> Imprimir ficha</button></div>
    <p class="nota">Para retirar un producto del catálogo activo, edítalo y cambia su estado a inactivo. Sus unidades y documentos se conservan.</p>
    <x-print-report title="Ficha de producto" :reference="$producto['codigo']" :subtitle="$producto['nombre']">
        <h2>Identificación del producto</h2><table class="reporte-tabla reporte-ficha"><tbody>
            <tr><th scope="row">Nombre</th><td>{{ $producto['nombre'] }}</td></tr>
            @foreach (['codClase' => ['clases', 'Clase'], 'codMarca' => ['marcas', 'Marca'], 'codUm' => ['unidadesMedida', 'Unidad de medida']] as $campo => [$lista, $label])
                <tr><th scope="row">{{ $label }}</th><td>{{ collect($catalogos[$lista])->firstWhere('codigo', $producto[$campo])['nombre'] ?? 'Sin registrar' }}</td></tr>
            @endforeach
            @foreach (['modelo' => 'Modelo', 'color' => 'Color', 'stockMinimo' => 'Stock mínimo de referencia', 'estado' => 'Estado'] as $campo => $label)
                <tr><th scope="row">{{ $label }}</th><td>{{ $producto[$campo] ?? '—' }}</td></tr>
            @endforeach
        </tbody></table>
        <p class="reporte-nota">La ficha describe el producto. Sus unidades físicas se consultan en el listado de unidades correspondiente.</p>
    </x-print-report>
@endsection
