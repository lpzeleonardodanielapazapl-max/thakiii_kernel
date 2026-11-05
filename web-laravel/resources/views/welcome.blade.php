@extends('layouts.admin')
@section('titulo', 'Bienvenido')
@section('contenido')
<x-page-header title="Bienvenido a Thaki Voyage." description="Tu punto de partida para organizar el equipo y acompañar cada nueva aventura." eyebrow="BOLIVIA · VIAJES A MEDIDA"/>
<section class="hero-thaki">
    <div class="hero-texto"><span class="hero-etiqueta">EL PRÓXIMO CAMINO NOS ESPERA</span><h2>Viajes que conectan.<br><em>Detalles que importan.</em></h2><p>Accede al centro de operaciones para consultar el inventario, las herramientas de análisis y los directorios.</p><a class="boton boton-navy" href="{{ route('admin.index') }}">Entrar al panel <x-icon name="arrow"/></a></div><x-landscape/>
</section>
<div class="accesos-grid accesos-tres">
    <a class="tarjeta-acceso" href="{{ route('inventario.index') }}"><span class="icono-destacado"><x-icon name="box"/></span><h3>Inventario de trekking</h3><p>Productos y unidades físicas, en un solo lugar.</p><x-icon class="flecha" name="arrow"/></a>
    <a class="tarjeta-acceso" href="{{ route('inventario.abc') }}"><span class="icono-destacado"><x-icon name="chart"/></span><h3>Análisis del inventario</h3><p>Prioriza el control de tus equipos con ABC.</p><x-icon class="flecha" name="arrow"/></a>
    <a class="tarjeta-acceso" href="{{ route('personas.index') }}"><span class="icono-destacado"><x-icon name="users"/></span><h3>Personas y equipo</h3><p>Consulta personas, empleados y clientes.</p><x-icon class="flecha" name="arrow"/></a>
</div>
@endsection
