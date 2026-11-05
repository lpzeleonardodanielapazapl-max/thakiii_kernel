@extends('layouts.admin')
@section('titulo', 'Centro de operaciones')
@section('contenido')
<x-page-header title="Cada gran viaje empieza aquí." description="Bienvenido a tu espacio de trabajo. Preparemos juntos el próximo camino." eyebrow="THAKI VOYAGE · CENTRO DE OPERACIONES">
    <a class="boton boton-secundario" href="{{ route('inventario.index') }}"><x-icon name="box"/> Ver inventario</a>
    <a class="boton" href="{{ route('inventario.crear') }}"><x-icon name="plus"/> Registrar producto</a>
</x-page-header>
<section class="hero-thaki" aria-labelledby="titulo-hero">
    <div class="hero-texto"><span class="hero-etiqueta"><span class="punto-luz"></span> EQUIPAMIENTO & EXPEDICIONES</span>
        <h2 id="titulo-hero">Cuidamos el equipo.<br><em>Inspiramos el viaje.</em></h2>
        <p>Detrás de una experiencia inolvidable hay un equipo listo y cada detalle en su lugar.</p>
        <a class="boton boton-navy" href="{{ route('inventario.index') }}">Explorar el inventario <x-icon name="arrow"/></a>
    </div>
    <x-landscape/><span class="hero-coordenada">16° 29′ S · 68° 08′ O &nbsp; / &nbsp; BOLIVIA</span>
</section>
@if ($error)<div class="aviso error" role="alert"><strong>No se pudieron cargar los indicadores.</strong><p>{{ $error }}</p><a href="{{ route('admin.index') }}">Volver a intentar</a></div>@endif
<div class="titulo-seccion titulo-en-linea"><div><p class="sobre-titulo">LO ESENCIAL, A LA VISTA</p><h2>Así está tu inventario</h2></div><a class="accion-enlace" href="{{ route('inventario.index') }}">Ver todos los productos <x-icon name="arrow"/></a></div>
<section class="indicadores" aria-label="Resumen del inventario">
    @foreach ([
        ['productos', 'Productos activos', 'Tipos de producto en el catálogo', 'box', '', 'azul'],
        ['unidades', 'Unidades registradas', 'Equipos vinculados a productos activos', 'grid', '', 'verde'],
        ['arreglo', 'Pendientes de arreglo', 'Equipos sin baja que requieren atención', 'briefcase', 'arreglo', 'durazno'],
        ['bajas', 'Bajas registradas', 'Unidades con fecha de baja', 'shield', 'bajas', 'coral'],
    ] as [$clave, $titulo, $detalle, $icono, $filtro, $tono])
        <a class="indicador indicador-{{ $tono }}" href="{{ route('inventario.index', $filtro ? ['filtro' => $filtro] : []) }}">
            <div class="indicador-titulo"><span>{{ $titulo }}</span><span class="indicador-icono"><x-icon :name="$icono"/></span></div>
            <strong>{{ $resumen ? number_format($resumen[$clave], 0, ',', '.') : '—' }}</strong><small>{{ $detalle }}</small>
            <span class="indicador-flecha" aria-hidden="true">↗</span>
        </a>
    @endforeach
</section>
<p class="nota">Las unidades registradas no equivalen a equipos disponibles. Los indicadores incluyen los registros de demostración existentes.</p>
<div class="panel-inferior">
    <section class="herramientas">
        <div class="titulo-seccion"><p class="sobre-titulo">DECISIONES CON INFORMACIÓN</p><h2>Un paso adelante</h2></div>
        <a class="acceso-fila" href="{{ route('inventario.abc') }}"><span class="numero"><x-icon name="chart"/></span><div><h3>Prioridades del inventario</h3><p>Descubre qué productos concentran el mayor valor con ABC.</p></div><x-icon class="flecha" name="arrow"/></a>
        <a class="acceso-fila" href="{{ route('io.index') }}"><span class="numero"><x-icon name="calculator"/></span><div><h3>Planifica tus compras</h3><p>Compara cantidades y costos antes de reponer con EOQ.</p></div><x-icon class="flecha" name="arrow"/></a>
        <a class="acceso-fila" href="{{ route('inventario.index') }}"><span class="numero"><x-icon name="box"/></span><div><h3>Cada equipo, con su código</h3><p>Abre un producto para consultar y registrar sus unidades.</p></div><x-icon class="flecha" name="arrow"/></a>
    </section>
    <section class="atencion">
        <span class="atencion-etiqueta">CUIDADO DEL EQUIPO</span>
        <h2>Los detalles<br>hacen la diferencia.</h2>
        @if (! $resumen)<p>No fue posible consultar los equipos pendientes de arreglo.</p>
        @elseif ($resumen['arreglo'] > 0)<div class="atencion-cantidad">{{ $resumen['arreglo'] }}</div><p>{{ $resumen['arreglo'] == 1 ? 'equipo requiere' : 'equipos requieren' }} revisión antes de planificar su uso.</p>
        @else<p class="atencion-mensaje">Sin arreglos pendientes registrados.</p><p>Revisa la condición de tus equipos antes de cada salida.</p>@endif
        <a href="{{ route('inventario.index', ['filtro' => 'arreglo']) }}" class="enlace-atencion">Revisar equipos <x-icon name="arrow"/></a>
    </section>
</div>
<section class="seccion-directorios">
    <div class="titulo-seccion titulo-en-linea"><div><p class="sobre-titulo">LAS PERSONAS DETRÁS DEL VIAJE</p><h2>Tu equipo y tus clientes</h2></div></div>
    <div class="accesos-grid">
        @foreach ([
            ['personas.index', 'Personas', 'El registro que conecta a todos.', 'users'],
            ['empleados.index', 'Empleados', 'El equipo de cada experiencia.', 'briefcase'],
            ['clientes.index', 'Clientes', 'Quienes eligen explorar contigo.', 'mountain'],
            ['usuarios.index', 'Usuarios', 'Las cuentas del sistema.', 'users'],
            ['roles.index', 'Roles', 'Responsabilidades y accesos.', 'shield'],
        ] as [$ruta, $titulo, $detalle, $icono])
            <a class="tarjeta-acceso" href="{{ route($ruta) }}"><span class="icono-destacado"><x-icon :name="$icono"/></span><h3>{{ $titulo }}</h3><p>{{ $detalle }}</p><x-icon class="flecha" name="arrow"/></a>
        @endforeach
    </div>
</section>
@endsection
