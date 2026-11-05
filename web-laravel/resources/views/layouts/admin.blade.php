<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('titulo', 'Administración') · Thaki Voyage</title>

    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=2">
</head>
<body>

<a class="saltar-contenido" href="#contenido-principal">
    Saltar al contenido
</a>

<div class="admin-shell">
    <aside class="sidebar">
        <a class="marca" href="{{ route('admin.index') }}" aria-label="Thaki Voyage, inicio">
            <svg class="marca-montana" viewBox="0 0 100 45" aria-hidden="true">
                <path d="M5 40 35 7 56 30 70 15 95 40"
                      fill="none" stroke="currentColor" stroke-width="2"/>
                <path d="m25 18 10 5 8-7"
                      fill="none" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span>THAKI</span>
            <small>VOYAGE</small>
        </a>

        <nav class="menu-admin" aria-label="Menú de administración">
            <p class="menu-etiqueta">GENERAL</p>

            <a href="{{ route('admin.index') }}"
               class="{{ request()->is('admin') ? 'activo' : '' }}"
               @if(request()->is('admin')) aria-current="page" @endif>
                <span class="menu-icono" aria-hidden="true">⌂</span>
                Inicio
            </a>

            <p class="menu-etiqueta">EQUIPAMIENTO</p>

            <a href="{{ url('/inventario') }}"
               class="{{ request()->is('inventario') || request()->is('inventario/*/reposicion') ? 'activo' : '' }}"
               @if(request()->is('inventario') || request()->is('inventario/*/reposicion')) aria-current="page" @endif>
                <span class="menu-icono" aria-hidden="true">▦</span>
                Inventario
            </a>

            <a href="{{ route('inventario.crear') }}"
               class="{{ request()->routeIs('inventario.crear') ? 'activo' : '' }}"
               @if(request()->routeIs('inventario.crear')) aria-current="page" @endif>
                <span class="menu-icono" aria-hidden="true">＋</span>
                Registrar producto
            </a>

            <a href="{{ url('/inventario/abc') }}"
               class="{{ request()->is('inventario/abc') ? 'activo' : '' }}"
               @if(request()->is('inventario/abc')) aria-current="page" @endif>
                <span class="menu-icono" aria-hidden="true">◫</span>
                Clasificación ABC
            </a>

            <p class="menu-etiqueta">PERSONAS Y ACCESOS</p>

            @foreach ([
                'personas' => 'Personas',
                'empleados' => 'Empleados',
                'clientes' => 'Clientes',
                'usuarios' => 'Usuarios',
                'roles' => 'Roles'
            ] as $ruta => $nombre)
                <a href="{{ url('/'.$ruta) }}"
                   class="{{ request()->is($ruta, $ruta.'/*') ? 'activo' : '' }}"
                   @if(request()->is($ruta, $ruta.'/*')) aria-current="page" @endif>
                    <span class="menu-icono menu-punto" aria-hidden="true">○</span>
                    {{ $nombre }}
                </a>
            @endforeach
        </nav>

        <div class="sidebar-pie">
            <span class="sello">HECHO PARA EXPLORAR</span>
            <p>El próximo viaje empieza<br>con cada detalle.</p>
        </div>
    </aside>

    <div class="contenido">
        <header class="barra-superior">
            <div class="breadcrumb">
                <span>Thaki Voyage</span>
                <span aria-hidden="true">/</span>
                <strong>Administración</strong>
            </div>

            <span class="etiqueta">Gestión interna</span>
        </header>

        <main class="pagina" id="contenido-principal" tabindex="-1">
            @yield('contenido')
        </main>

        <footer class="pie-pagina">
            <span>THAKI VOYAGE · Turismo a medida</span>
            <span>Entorno de desarrollo · Acceso por rol pendiente</span>
        </footer>
    </div>
</div>

</body>
</html>