@extends('layouts.admin')
@section('titulo', 'Inventario')
@section('contenido')
<x-page-header title="Listos para la aventura." description="Cada producto, cada equipo, cada detalle. Gestiona tu inventario desde un solo lugar." eyebrow="EQUIPAMIENTO · ALMACÉN PRINCIPAL">
    <a class="boton boton-secundario" href="{{ route('inventario.abc') }}"><x-icon name="chart"/> Ver ABC</a>
    <a class="boton" href="{{ route('inventario.crear') }}"><x-icon name="plus"/> Registrar producto</a>
</x-page-header>
<div class="banda-contexto">
    <span class="icono-destacado"><x-icon name="box"/></span>
    <div><strong>El equipo detrás de cada experiencia.</strong><p>Abre un producto para consultar o registrar sus unidades físicas.</p></div>
    <a class="accion-enlace" href="{{ route('io.index') }}">Planificar compras <x-icon name="arrow"/></a>
</div>
<section class="panel panel-tabla" data-table>
    <form class="table-tools no-imprimir" method="GET" action="{{ route('inventario.index') }}">
        <label class="selector-filtro">Estado del catálogo <select name="estado"><option value="ACTIVO" @selected(request('estado', 'ACTIVO') === 'ACTIVO')>Productos activos</option><option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>Productos inactivos</option><option value="TODOS" @selected(request('estado') === 'TODOS')>Todos los productos</option></select></label><button type="submit" class="boton boton-secundario boton-pequeno">Consultar</button>
    </form>
    <div class="titulo-tabla"><div><span class="sobre-titulo">TU CATÁLOGO</span><h2>Productos <span class="total-badge">{{ count($productos) }}</span></h2></div><div class="acciones-pagina no-imprimir"><a class="enlace-icono" href="{{ route('inventario.index', request()->only('estado')) }}"><x-icon name="refresh"/> Actualizar</a><button class="boton boton-secundario boton-pequeno" type="button" data-print><x-icon name="print"/> Imprimir</button></div></div>
    <x-table-tools label="Buscar producto, código o marca" :filters="['arreglo' => 'Con equipos para arreglo', 'bajas' => 'Con unidades dadas de baja', 'sin-unidades' => 'Sin unidades registradas']"/>
    <div class="tabla-scroll"><table class="tabla-inventario">
        <caption class="solo-lectores">Inventario de productos y unidades físicas</caption>
        <thead><tr><th scope="col">Producto</th><th scope="col">Categoría</th><th scope="col">Unidades</th><th scope="col">Para arreglo</th><th scope="col">Bajas</th><th scope="col" class="no-imprimir">Acciones</th></tr></thead>
        <tbody>
        @forelse ($productos as $producto)
            <tr data-row data-tags="{{ $producto['unidadesRequierenArreglo'] > 0 ? 'arreglo' : '' }} {{ $producto['unidadesConBaja'] > 0 ? 'bajas' : '' }} {{ $producto['unidadesRegistradas'] == 0 ? 'sin-unidades' : '' }}">
                <td><div class="producto-celda"><span class="mini-producto"><x-icon name="box"/></span><div><strong>{{ $producto['nombre'] }}</strong><small>{{ $producto['codigo'] }} · {{ $producto['marca'] }}</small></div></div></td>
                <td><span class="badge badge-azul">{{ $producto['categoria'] }}</span></td>
                <td class="cifra-tabla">{{ $producto['unidadesRegistradas'] }}</td>
                <td><span @class(['badge', 'badge-durazno' => $producto['unidadesRequierenArreglo'] > 0, 'badge-gris' => $producto['unidadesRequierenArreglo'] == 0])>{{ $producto['unidadesRequierenArreglo'] }}</span></td>
                <td>{{ $producto['unidadesConBaja'] }}</td>
                <td class="no-imprimir"><div class="acciones-fila">
                    <a class="boton boton-pequeno boton-navy" href="{{ route('inventario.unidades', ['codigo' => $producto['codigo']]) }}">Ver / registrar unidades <x-icon name="arrow"/></a>
                    <a class="accion-enlace" href="{{ route('inventario.producto', ['codigo' => $producto['codigo']]) }}">Detalles / editar producto</a>
                    @if (($producto['estado'] ?? 'ACTIVO') === 'ACTIVO')<a class="accion-enlace" href="{{ route('inventario.analizar', ['codigo' => $producto['codigo']]) }}">Analizar reposición</a>@else<span class="badge badge-gris">Inactivo</span>@endif
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="estado-vacio"><x-icon name="box"/><strong>Tu inventario empieza aquí.</strong><span>Registra el primer producto y después añade sus unidades físicas.</span><a class="boton" href="{{ route('inventario.crear') }}">Registrar producto</a></td></tr>
        @endforelse
            <tr data-no-results hidden><td colspan="6" class="estado-vacio">No encontramos productos con estos filtros. Prueba con otra búsqueda.</td></tr>
        </tbody>
    </table></div>
    <p class="nota nota-tabla">Las unidades registradas no equivalen a disponibilidad. Para utilizar un equipo también deben comprobarse sus salidas, recepciones y condición.</p>
</section>
@endsection
