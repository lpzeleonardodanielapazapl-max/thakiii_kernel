@extends('layouts.admin')
@section('titulo', 'Centro de operaciones')
@section('clase-pagina', 'inicio-operaciones')
@section('contenido')
<header class="operaciones-cabecera"><div><p class="sobre-titulo">TU ESPACIO DE TRABAJO</p><h1>Centro de operaciones</h1><p class="descripcion">Atención, viajes y almacén, cada uno en su lugar.</p></div></header>
<section class="operaciones-accesos" aria-label="Trabajo diario">
    @foreach([
        ['clientes.index', 'Clientes', 'Solicitudes, conversaciones, propuestas y viajes de cada cliente.', 'users', 'Ver clientes'],
    ] as [$ruta, $titulo, $detalle, $icono, $accion])
    <a class="operaciones-acceso" href="{{ route($ruta) }}"><span class="operaciones-icono"><x-icon :name="$icono"/></span><h2>{{ $titulo }}</h2><p>{{ $detalle }}</p><span class="operaciones-accion">{{ $accion }} <x-icon name="arrow"/></span></a>
    @endforeach
</section>
<section class="operaciones-resumen" aria-labelledby="resumen-inventario">
    <div class="operaciones-seccion"><div><p class="sobre-titulo">EQUIPOS Y MATERIALES</p><h2 id="resumen-inventario">Inventario y almacén</h2><p class="operaciones-nota">Alpinismo, cocina y eventos. El control del Excel reunido en un solo apartado.</p></div><a class="boton" href="{{ route('inventario.operaciones') }}">Abrir inventario <x-icon name="arrow"/></a></div>
    <nav class="operaciones-inventario-enlaces" aria-label="Herramientas del inventario">
        @foreach(['existencias'=>'Existencias', 'referencias'=>'Recuento del Excel', 'documentos'=>'Documentos NI / NS', 'prestamos'=>'Custodia', 'movimientos'=>'Movimientos', 'precios'=>'Precios'] as $ver=>$nombre)<a href="{{ route('inventario.operaciones',['ver'=>$ver]) }}">{{ $nombre }} <x-icon name="arrow"/></a>@endforeach
    </nav>
    @if($error)<div class="aviso error" role="alert"><strong>El resumen no está disponible.</strong><p>Puedes seguir usando los accesos del panel e intentar cargar los datos otra vez.</p><a href="{{ route('admin.index') }}">Volver a intentar</a></div>
    @else
    <dl class="operaciones-cifras">
        <div><dt>Productos activos</dt><dd>{{ number_format($resumen['productos'], 0, ',', '.') }}</dd></div>
        <div><dt>Unidades registradas</dt><dd>{{ number_format($resumen['unidades'], 0, ',', '.') }}</dd></div>
        <div><dt>Requieren arreglo</dt><dd><a href="{{ route('inventario.index', ['filtro' => 'arreglo']) }}">{{ number_format($resumen['arreglo'], 0, ',', '.') }} <span aria-hidden="true">↗</span></a></dd></div>
    </dl>
    <p class="operaciones-nota">Las unidades registradas no indican disponibilidad para un viaje. Los datos DEMO se excluyen de estos totales.</p>
    @endif
</section>
<aside class="operaciones-mas"><div><h2>Equipo y servicios del viaje</h2><p>Personal, hoteles, transporte y accesos están reunidos en Gestión.</p></div><a class="boton boton-secundario" href="{{ route('admin.gestion') }}"><x-icon name="grid"/> Ir a Gestión</a></aside>
@endsection
