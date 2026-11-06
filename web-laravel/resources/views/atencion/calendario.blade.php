@extends('layouts.admin')
@section('titulo', 'Calendario de viajes')
@push('styles')<link rel="stylesheet" href="{{ asset('css/calendario.css') }}?v={{ filemtime(public_path('css/calendario.css')) }}" media="screen">@endpush
@section('contenido')
<x-page-header title="Calendario de viajes" description="Viajes con aceptación del cliente registrada. Consulta sus fechas y continúa con cada reserva." eyebrow="TU AGENDA DE VIAJES">
    <a class="boton boton-secundario" href="{{ route('atencion.index') }}">Atención a clientes ↗</a>
</x-page-header>
<section class="agenda-calendario" aria-label="Calendario mensual">
    <header class="agenda-cabecera">
        <div><h2>{{ $mes->locale('es')->translatedFormat('F Y') }}</h2><p>{{ session('usuario.rol') === 'ADMIN' ? 'Todos los viajes aceptados' : 'Viajes de tus clientes asignados' }}</p></div>
        <nav class="agenda-navegacion" aria-label="Cambiar mes">
            @if($mes->format('Y-m') > '2020-01')<a href="{{ route('atencion.calendario', ['mes' => $mes->copy()->subMonth()->format('Y-m')]) }}" aria-label="Mes anterior">←</a>@endif
            <a href="{{ route('atencion.calendario') }}">Hoy</a>
            @if($mes->format('Y-m') < '2100-12')<a href="{{ route('atencion.calendario', ['mes' => $mes->copy()->addMonth()->format('Y-m')]) }}" aria-label="Mes siguiente">→</a>@endif
        </nav>
    </header>
    <form class="agenda-filtro" method="GET" action="{{ route('atencion.calendario') }}"><label for="agenda-mes">Ir al mes<input id="agenda-mes" type="month" name="mes" min="2020-01" max="2100-12" value="{{ $mes->format('Y-m') }}" required></label><button class="boton boton-secundario boton-pequeno" type="submit">Ver mes</button></form>
    <div class="agenda-leyenda"><span><i class="agenda-punto pendiente"></i>Aceptado · pendiente de anticipo</span><span><i class="agenda-punto confirmado"></i>Confirmado · anticipo verificado</span></div>
    <div class="agenda-scroll" tabindex="0" role="region" aria-label="Fechas y viajes; desliza horizontalmente en pantallas pequeñas">
    <table class="agenda-tabla"><caption class="solo-lectores">{{ $mes->locale('es')->translatedFormat('F Y') }}. Cada día permite consultar su agenda.</caption><thead><tr>@foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $nombre)<th scope="col">{{ $nombre }}</th>@endforeach</tr></thead><tbody>
        @foreach(array_chunk($dias, 7) as $semana)<tr>@foreach($semana as $dia)
            @php($fecha = $dia['fecha'])
            <td @class(['agenda-otro-mes' => $fecha->month !== $mes->month, 'agenda-hoy' => $fecha->toDateString() === now('America/La_Paz')->toDateString(), 'agenda-seleccionado' => $fecha->toDateString() === $diaSeleccionado])>
                <a class="agenda-dia" href="{{ route('atencion.calendario', ['mes' => $mes->format('Y-m'), 'dia' => $fecha->toDateString()]) }}#agenda-viajes" aria-label="Ver agenda del {{ $fecha->format('d/m/Y') }}" @if($fecha->toDateString() === now('America/La_Paz')->toDateString()) aria-current="date" @endif>{{ $fecha->day }}</a>
                @foreach(array_slice($dia['viajes'], 0, 3) as $viaje)<a @class(['agenda-evento', 'confirmado' => $viaje['estado'] === 'CONFIRMADA']) href="{{ route('atencion.reserva', $viaje['reserva']) }}" title="{{ $viaje['titulo'] }} · {{ $viaje['cliente'] }}"><span>{{ $viaje['titulo'] }}</span><small>{{ $viaje['cliente'] }}</small></a>@endforeach
                @if(count($dia['viajes']) > 3)<a class="agenda-mas" href="{{ route('atencion.calendario', ['mes' => $mes->format('Y-m'), 'dia' => $fecha->toDateString()]) }}#agenda-viajes">+{{ count($dia['viajes']) - 3 }} viajes</a>@endif
            </td>
        @endforeach</tr>@endforeach
    </tbody></table></div>
</section>
<section class="agenda-lista" id="agenda-viajes">
    <div class="agenda-lista-titulo"><h2>{{ $diaSeleccionado ? 'Viajes del '.\Illuminate\Support\Carbon::parse($diaSeleccionado)->format('d/m/Y') : 'Viajes de este mes' }}</h2>@if($diaSeleccionado)<a href="{{ route('atencion.calendario', ['mes' => $mes->format('Y-m')]) }}#agenda-viajes">Ver todo el mes ↗</a>@endif</div>
    @forelse($agenda as $viaje)<article class="agenda-reserva"><div class="agenda-fechas"><strong>{{ \Illuminate\Support\Carbon::parse($viaje['fechaInicio'])->locale('es')->translatedFormat('d M') }}</strong><span>hasta {{ \Illuminate\Support\Carbon::parse($viaje['fechaFin'])->locale('es')->translatedFormat('d M Y') }}</span></div><div class="agenda-reserva-datos"><h3>{{ $viaje['titulo'] }}</h3><p>{{ $viaje['cliente'] }} · {{ $viaje['personas'] }} {{ $viaje['personas'] == 1 ? 'viajero' : 'viajeros' }} · Propuesta {{ $viaje['version'] }}</p><x-viaje-estado :value="$viaje['estado']"/></div><a class="boton boton-secundario boton-pequeno" href="{{ route('atencion.reserva', $viaje['reserva']) }}">Ver reserva ↗</a></article>
    @empty<div class="agenda-vacio"><x-icon name="calendar"/><h3>{{ $diaSeleccionado ? 'No hay viajes aceptados para este día.' : 'Un mes por llenar de caminos.' }}</h3><p>Los viajes aparecerán aquí cuando se registre la aceptación del cliente. Los cancelados quedan fuera de la agenda.</p><a href="{{ route('atencion.index') }}">Revisar solicitudes ↗</a></div>@endforelse
</section>
@endsection
