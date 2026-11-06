<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Administración') · Thaki Voyage</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/print.css') }}?v={{ filemtime(public_path('css/print.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/viajes.css') }}?v={{ filemtime(public_path('css/viajes.css')) }}">
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
    @stack('styles')
    @stack('scripts')
    <link rel="stylesheet" href="{{ asset('css/liquid.css') }}?v={{ filemtime(public_path('css/liquid.css')) }}" media="screen">
    <script src="{{ asset('js/liquid.js') }}?v={{ filemtime(public_path('js/liquid.js')) }}" defer></script>
</head>
<body class="estilo-liquid @yield('clase-pagina')">
<x-liquid-filter/>
<a class="saltar-contenido" href="#contenido-principal">Saltar al contenido</a>
<div class="admin-shell">
    <aside class="sidebar">
        <a class="marca" href="{{ route('admin.index') }}" aria-label="Thaki Voyage, inicio">
            <span class="marca-simbolo"><x-icon name="mountain"/></span>
            <span class="marca-nombre">THAKI<small>V O Y A G E</small></span>
        </a>
        <div class="sidebar-contexto"><span class="punto-luz"></span> Centro de operaciones</div>
        <button class="boton-menu" type="button" aria-expanded="true" aria-controls="navegacion-admin"><x-icon name="menu"/> Menú de navegación</button>
        <nav class="menu-admin" id="navegacion-admin" aria-label="Menú de administración">
            @php
                $gestionActiva = request()->routeIs('admin.gestion') || request()->is('personas*', 'empleados*', 'cargos*', 'departamentos*', 'usuarios*', 'roles*', 'inventario/abc', 'investigacion-operativa*', 'inventario/*/reposicion');
                $enlaces = [
                    ['admin.index', 'Inicio', 'home', request()->routeIs('admin.index')],
                    ['atencion.index', 'Atención a clientes', 'mountain', request()->is('atencion*') && !request()->routeIs('atencion.calendario')],
                    ['atencion.calendario', 'Calendario de viajes', 'calendar', request()->routeIs('atencion.calendario')],
                    ['inventario.index', 'Inventario', 'box', request()->is('inventario*') && !$gestionActiva],
                    ['clientes.index', 'Clientes', 'users', request()->is('clientes*')],
                ];
            @endphp
            <p class="menu-etiqueta">TRABAJO DIARIO</p>
            @foreach ($enlaces as [$ruta, $texto, $icono, $activo])
                <a href="{{ route($ruta) }}" @class(['activo' => $activo]) @if ($activo) aria-current="page" @endif><x-icon :name="$icono"/><span>{{ $texto }}</span>@if ($activo)<span class="nav-indicador"></span>@endif</a>
            @endforeach
            <div class="menu-gestion">
                <a href="{{ route('admin.gestion') }}" @class(['activo' => $gestionActiva]) @if ($gestionActiva) aria-current="page" @endif><x-icon name="grid"/><span>Gestión</span>@if ($gestionActiva)<span class="nav-indicador"></span>@endif</a>
                <p>Equipo, accesos y análisis.</p>
            </div>
        </nav>
        <div class="sidebar-pie sidebar-pie-simple"><span>THAKI VOYAGE · BOLIVIA</span><small>{{ session('usuario.rol') === 'ADMIN' ? 'Administración' : 'Equipo de trabajo' }}</small></div>
    </aside>
    <div class="contenido">
        <header class="barra-superior no-imprimir">
            <nav class="breadcrumb" aria-label="Ruta de navegación">
                <a href="{{ route('admin.index') }}"><x-icon name="home"/><span>Panel</span></a>
                @if ($gestionActiva && !request()->routeIs('admin.gestion'))<span aria-hidden="true">/</span><a href="{{ route('admin.gestion') }}">Gestión</a>@endif
                @if (request()->is('inventario/*'))<span aria-hidden="true">/</span><a href="{{ route('inventario.index') }}">Inventario</a>@endif
                @if (request()->route('modulo') && ! request()->routeIs(request()->route('modulo').'.index'))<span aria-hidden="true">/</span><a href="{{ route(request()->route('modulo').'.index') }}">{{ config('gestion.'.request()->route('modulo').'.title') }}</a>@endif
                <span aria-hidden="true">/</span><strong aria-current="page">@yield('titulo', 'Administración')</strong>
            </nav>
            <div class="barra-detalle"><a href="{{ route('publico.inicio') }}">Ver web</a><span>{{ session('usuario.nombre', 'Thaki Voyage') }}</span><form method="POST" action="{{ route('acceso.salir') }}">@csrf<button class="boton boton-secundario boton-pequeno" type="submit">Salir</button></form></div>
        </header>
        <main class="pagina" id="contenido-principal" tabindex="-1">@yield('contenido')</main>
        <footer class="pie-pagina"><span>THAKI VOYAGE · Viajes que dejan huella.</span><a href="{{ route('admin.index') }}">Volver al panel ↗</a></footer>
    </div>
</div>
@stack('impresion')
</body>
</html>
