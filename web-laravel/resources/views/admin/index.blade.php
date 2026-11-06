@extends('layouts.admin')
@section('titulo', 'Centro de operaciones')
@section('clase-pagina', 'inicio-operaciones')
@section('contenido')
<header class="operaciones-cabecera"><div><p class="sobre-titulo">TU ESPACIO DE TRABAJO</p><h1>Centro de operaciones</h1><p class="descripcion">Lo esencial para continuar con tu día.</p></div><a class="operaciones-enlace" href="{{ route('atencion.calendario') }}">Calendario de viajes <x-icon name="calendar"/></a></header>
<section class="operaciones-accesos" aria-label="Trabajo diario">
    @foreach([
        ['atencion.index', 'Atención a clientes', 'Continúa solicitudes, conversaciones y propuestas de viaje.', 'mountain', 'Continuar atención'],
        ['inventario.index', 'Inventario', 'Consulta productos, unidades y su condición.', 'box', 'Revisar inventario'],
        ['clientes.index', 'Clientes', 'Consulta y actualiza las fichas de tus viajeros.', 'users', 'Ver clientes'],
    ] as [$ruta, $titulo, $detalle, $icono, $accion])
    <a class="operaciones-acceso" href="{{ route($ruta) }}"><span class="operaciones-icono"><x-icon :name="$icono"/></span><h2>{{ $titulo }}</h2><p>{{ $detalle }}</p><span class="operaciones-accion">{{ $accion }} <x-icon name="arrow"/></span></a>
    @endforeach
</section>
<section class="operaciones-resumen" aria-labelledby="resumen-inventario">
    <div class="operaciones-seccion"><h2 id="resumen-inventario">Inventario de un vistazo</h2><a class="operaciones-enlace" href="{{ route('inventario.index') }}">Ver detalle <x-icon name="arrow"/></a></div>
    @if($error)<div class="aviso error" role="alert"><strong>El resumen no está disponible.</strong><p>Puedes seguir usando los accesos del panel e intentar cargar los datos otra vez.</p><a href="{{ route('admin.index') }}">Volver a intentar</a></div>
    @else
    <dl class="operaciones-cifras">
        <div><dt>Productos activos</dt><dd>{{ number_format($resumen['productos'], 0, ',', '.') }}</dd></div>
        <div><dt>Unidades registradas</dt><dd>{{ number_format($resumen['unidades'], 0, ',', '.') }}</dd></div>
        <div><dt>Requieren arreglo</dt><dd><a href="{{ route('inventario.index', ['filtro' => 'arreglo']) }}">{{ number_format($resumen['arreglo'], 0, ',', '.') }} <span aria-hidden="true">↗</span></a></dd></div>
    </dl>
    <p class="operaciones-nota">Las unidades registradas no indican disponibilidad para un viaje.</p>
    @endif
</section>
<aside class="operaciones-mas"><div><h2>¿Buscas otra herramienta?</h2><p>El equipo, los accesos y los análisis están reunidos en Gestión.</p></div><a class="boton boton-secundario" href="{{ route('admin.gestion') }}"><x-icon name="grid"/> Ir a Gestión</a></aside>
@endsection
