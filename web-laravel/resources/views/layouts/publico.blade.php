<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Viajes a tu medida') · Thaki Voyage</title>
    <meta name="description" content="Descubre Bolivia y diseña tu viaje a medida con Thaki Voyage.">
    <link rel="stylesheet" href="{{ asset('css/publico.css') }}?v={{ filemtime(public_path('css/publico.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/print.css') }}?v={{ filemtime(public_path('css/print.css')) }}">
    <script src="{{ asset('js/publico.js') }}?v={{ filemtime(public_path('js/publico.js')) }}" defer></script>
</head>
<body class="@yield('clase', 'pagina-publica')">
<a class="saltar" href="#contenido">Saltar al contenido</a>
<div class="sitio">
<header class="cabecera">
    <a class="marca-publica" href="{{ route('publico.inicio') }}"><span>THAKI<small>V O Y A G E</small></span></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu-publico">Menú ☰</button>
    <nav id="menu-publico" aria-label="Navegación principal">
        <a href="{{ route('publico.inicio') }}#inspiraciones">Explora Bolivia</a><a href="{{ route('publico.inicio') }}#nosotros">Nuestra esencia</a><a href="{{ route('publico.inicio') }}#como-viajar">Cómo viajamos</a>
        @if(session('usuario'))
            <a class="boton pequeno" href="{{ route(session('usuario.rol') === 'CLIENTE' ? 'portal.inicio' : 'admin.index') }}">Mi espacio ↗</a>
        @else
            <a href="{{ route('acceso.ingresar') }}">Ingresar</a><a class="boton pequeno" href="{{ route('acceso.registro') }}">Diseña tu viaje ↗</a>
        @endif
    </nav>
</header>
<main id="contenido">@yield('contenido')</main>
<footer class="pie"><a class="marca-publica" href="{{ route('publico.inicio') }}">THAKI <small>VOYAGE</small></a><p>Bolivia, a tu manera.<br>Viajes que comienzan con una conversación.</p><div><a href="{{ route('publico.inicio') }}#contacto">Contacto</a><a href="{{ route('acceso.ingresar') }}">Tu espacio privado</a><small>© {{ date('Y') }} Thaki Voyage</small></div></footer>
</div>
@stack('impresion')
</body></html>
