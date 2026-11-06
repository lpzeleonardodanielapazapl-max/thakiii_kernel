<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Administración') · Thaki Voyage</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/print.css') }}?v={{ filemtime(public_path('css/print.css')) }}">
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
</head>
<body class="@yield('clase-pagina')">
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
                $grupos = [
                    'TU ESPACIO' => [['admin.index', 'Inicio', 'home', request()->is('admin')], ['atencion.index', 'Atención a clientes', 'users', request()->is('atencion*')]],
                    'EQUIPAMIENTO' => [
                        ['inventario.index', 'Inventario', 'box', request()->is('inventario', 'inventario/*/unidades', 'inventario/*/unidades/*', 'inventario/productos/*') && ! request()->routeIs('inventario.crear')],
                        ['inventario.crear', 'Registrar producto', 'plus', request()->routeIs('inventario.crear')],
                    ],
                    'ANÁLISIS Y COMPRAS' => [
                        ['inventario.abc', 'Clasificación ABC', 'chart', request()->routeIs('inventario.abc')],
                        ['io.index', 'Reposición · EOQ', 'calculator', request()->is('investigacion-operativa*', 'inventario/*/reposicion')],
                    ],
                    'PERSONAS Y ACCESOS' => [
                        ['personas.index', 'Personas', 'users', request()->is('personas', 'personas/*')],
                        ['empleados.index', 'Empleados', 'briefcase', request()->is('empleados', 'empleados/*')],
                        ['cargos.index', 'Cargos', 'briefcase', request()->is('cargos', 'cargos/*')],
                        ['departamentos.index', 'Departamentos', 'grid', request()->is('departamentos', 'departamentos/*')],
                        ['clientes.index', 'Clientes', 'mountain', request()->is('clientes', 'clientes/*')],
                        ['usuarios.index', 'Usuarios', 'users', request()->is('usuarios', 'usuarios/*')],
                        ['roles.index', 'Roles', 'shield', request()->is('roles', 'roles/*')],
                    ],
                ];
            @endphp
            @foreach ($grupos as $grupo => $enlaces)
                <p class="menu-etiqueta">{{ $grupo }}</p>
                @foreach ($enlaces as [$ruta, $texto, $icono, $activo])
                    <a href="{{ route($ruta) }}" @class(['activo' => $activo]) @if ($activo) aria-current="page" @endif>
                        <x-icon :name="$icono"/><span>{{ $texto }}</span>@if ($activo)<span class="nav-indicador"></span>@endif
                    </a>
                @endforeach
            @endforeach
        </nav>
        <div class="sidebar-pie"><x-icon name="mountain"/><p>Todo listo para<br><em>el próximo camino.</em></p><span>BOLIVIA · THAKI VOYAGE</span></div>
    </aside>
    <div class="contenido">
        <header class="barra-superior no-imprimir">
            <nav class="breadcrumb" aria-label="Ruta de navegación">
                <a href="{{ route('admin.index') }}"><x-icon name="home"/><span>Panel</span></a>
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
