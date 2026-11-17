@extends('layouts.admin')
@section('titulo', 'Clientes')
@push('styles')<link rel="stylesheet" href="{{ asset('css/clientes.css') }}?v={{ filemtime(public_path('css/clientes.css')) }}">@endpush
@section('contenido')
<x-page-header title="Clientes" description="Abre una ficha para consultar sus solicitudes, viajes y conversaciones.">
    <a class="boton boton-secundario" href="{{ route('atencion.calendario') }}"><x-icon name="calendar"/> Calendario de viajes</a>
    <a class="boton" href="{{ route('clientes.create') }}"><x-icon name="plus"/> Registrar cliente</a>
</x-page-header>
@include('components.avisos-publicos')
<section class="panel clientes-panel">
    <form class="clientes-filtros" method="GET" action="{{ route('clientes.index') }}">
        <label for="buscar-cliente">Buscar cliente</label>
        <div class="clientes-busqueda"><input id="buscar-cliente" name="q" value="{{ $busqueda }}" placeholder="Nombre, correo o teléfono" maxlength="150"><select name="ver" aria-label="Filtrar clientes">@foreach(['todos'=>'Todos','nuevos'=>'Nuevos','pendientes'=>'Con avisos'] as $valor=>$texto)<option value="{{ $valor }}" @selected($filtro===$valor)>{{ $texto }}</option>@endforeach</select><button class="boton" type="submit">Buscar</button></div>
    </form>
    <p class="clientes-conteo">{{ $clientes->count() }} clientes · Los avisos corresponden a tu cuenta de trabajo.</p>
    <ul class="clientes-lista">
        @forelse($clientes as $cliente)
        <li><a class="cliente-fila" href="{{ route('clientes.show', $cliente['codigo']) }}">
            <span class="cliente-avatar" aria-hidden="true">{{ mb_substr($cliente['nombre'],0,1) }}</span>
            <span class="cliente-identidad"><strong>{{ $cliente['nombre'] }}</strong><small>{{ $cliente['email'] ?: 'Sin correo registrado' }}</small><small>{{ $cliente['telefono'] }}</small></span>
            <span class="cliente-avisos">@if($cliente['revisado'] === null)<span class="cliente-etiqueta">Nuevo</span>@endif
            @if($cliente['solicitudesNuevas'] + $cliente['mensajesNuevos'] + ($cliente['reservasNuevas'] ?? 0) > 0)<span class="cliente-alerta"><x-icon name="bell"/> {{ $cliente['solicitudesNuevas'] }} solicitudes nuevas · {{ $cliente['mensajesNuevos'] }} mensajes sin leer @if($cliente['reservasNuevas'] ?? 0) · {{ $cliente['reservasNuevas'] }} reservas nuevas @endif</span>@else<small>Sin avisos pendientes</small>@endif</span>
            <x-icon name="arrow"/>
        </a></li>
        @empty<li class="clientes-vacio">No hay clientes que coincidan con estos filtros.</li>@endforelse
    </ul>
</section>
<x-print-report title="Clientes">
    <table class="reporte-tabla"><thead><tr><th>Cliente</th><th>Correo</th><th>Teléfono</th><th>Estado</th></tr></thead><tbody>@foreach($clientes as $cliente)<tr><td>{{ $cliente['nombre'] }}</td><td>{{ $cliente['email'] }}</td><td>{{ $cliente['telefono'] }}</td><td>{{ $cliente['estado'] }}</td></tr>@endforeach</tbody></table>
</x-print-report>
@endsection
