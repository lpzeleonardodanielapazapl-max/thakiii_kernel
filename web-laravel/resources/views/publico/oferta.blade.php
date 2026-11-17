@extends($enPortal ? 'layouts.portal' : 'layouts.publico')
@section('titulo', $modo === 'hotel' ? 'Alojamientos' : 'Traslados')
@section('publico-clase', 'publico-con-cabecera')
@section($enPortal ? 'portal' : 'contenido')
@php
    $esPersonal = in_array(session('usuario.rol'), ['ADMIN', 'ADMINISTRATIVO'], true);
    $foto = function (string $ciudad): string {
        $lugar = mb_strtolower($ciudad);
        $archivo = str_contains($lugar, 'uyuni') ? 'uyuni.jpg' : (str_contains($lugar, 'paz') ? 'la-paz.jpg' : ((str_contains($lugar, 'rurren') || str_contains($lugar, 'madidi')) ? 'amazonia.jpg' : 'illimani.jpg'));

        return asset('images/thaki/'.$archivo);
    };
@endphp
<div class="oferta-pagina">
<div class="portal-titulo"><div><span class="etiqueta">{{ $modo === 'hotel' ? 'DÓNDE DORMIR' : 'CÓMO MOVERTE' }}</span><h1>{{ $modo === 'hotel' ? 'Elige tu alojamiento' : 'Elige tu traslado' }}</h1><p>{{ $modo === 'hotel' ? 'Compara ciudad, habitación y precio por noche. Tu asesor confirmará la reserva.' : 'Elige el servicio. El equipo asigna el vehículo y el conductor.' }}</p></div><a class="enlace" href="{{ $enPortal ? route('portal.solicitud') : route('publico.inicio') }}">{{ $enPortal ? '← Volver a mi solicitud' : '← Inicio' }}</a></div>
<nav class="oferta-cambio" aria-label="Catálogo de viaje"><a href="{{ route('publico.alojamientos') }}" @if($modo === 'hotel') aria-current="page" @endif>Alojamientos</a><a href="{{ route('publico.transporte') }}" @if($modo === 'transporte') aria-current="page" @endif>Traslados</a></nav>
@if($esPersonal)<div class="aviso"><p>Catálogo que puede consultar el viajero. Gestiona sus opciones desde el panel del equipo.</p><div class="acciones"><a class="enlace" href="{{ route('admin.gestion') }}">← Gestión</a><a class="enlace" href="{{ route($modo === 'hotel' ? 'hoteles.index' : 'servicios-transporte.index') }}">{{ $modo === 'hotel' ? 'Gestionar alojamientos' : 'Gestionar traslados' }} ↗</a></div></div>@endif
@if($errorCatalogo)<p class="aviso error" role="alert">El catálogo no está disponible. Comprueba que Quarkus y MySQL estén en marcha.</p>
@elseif($modo === 'hotel' && !count($hoteles))<p class="aviso">Todavía no hay alojamientos activos. El equipo puede registrarlos desde Gestión.</p>
@elseif($modo === 'transporte' && !count($servicios))<p class="aviso">Todavía no hay traslados activos.</p>
@endif
@if($modo === 'hotel')
<div class="oferta-lista">
@foreach($hoteles as $hotel)
<article class="oferta-card">
    @php
        $tieneFoto = preg_match('~^https?://~i', $hotel['imagen'] ?? '') === 1;
    @endphp
    <div class="oferta-foto"><img src="{{ $tieneFoto ? $hotel['imagen'] : $foto($hotel['ciudad'] ?? '') }}" alt="{{ $tieneFoto ? $hotel['nombre'] : 'Referencia del destino: '.($hotel['ciudad'] ?: 'Bolivia') }}" loading="lazy">@unless($tieneFoto)<small>Imagen de referencia del destino</small>@endunless</div>
    <div>
        <span class="etiqueta">{{ $hotel['ciudad'] ?: 'Bolivia' }} @if(($hotel['categoria'] ?? 0) > 0)· {{ $hotel['categoria'] }} estrellas @endif</span>
        <h2>{{ $hotel['nombre'] }}</h2>
        <p>{{ $hotel['descripcion'] }}</p>
        <p class="oferta-meta">{{ $hotel['direccion'] }} @if($hotel['checkIn'])· Entrada {{ $hotel['checkIn'] }}, salida {{ $hotel['checkOut'] }}@endif</p>
        @if($hotel['servicios'])<p class="oferta-meta">{{ $hotel['servicios'] }}</p>@endif
        <div class="oferta-opciones">
            @forelse($hotel['habitaciones'] as $habitacion)
            <div class="oferta-opcion">
                <div><strong>{{ $habitacion['nombre'] }}</strong><span>{{ $habitacion['capacidad'] }} {{ $habitacion['capacidad'] == 1 ? 'persona' : 'personas' }} · {{ $habitacion['camas'] }} {{ $habitacion['camas'] == 1 ? 'cama' : 'camas' }} {{ $habitacion['tipoCamas'] }}</span><p>{{ $habitacion['descripcion'] }}</p></div>
                <div><b>{{ $habitacion['precioNoche'] }} {{ $habitacion['moneda'] }}</b><small>por noche @if((int) ($habitacion['desayuno'] ?? 0) === 1)· desayuno @endif</small>
                    @unless($esPersonal)<form method="POST" action="{{ route('publico.alojamientos.elegir') }}">@csrf<input type="hidden" name="hotel" value="{{ $hotel['codigo'] }}"><input type="hidden" name="habitacion" value="{{ $habitacion['codigo'] }}"><button class="boton pequeno" type="submit">Elegir</button></form>@endunless
                </div>
            </div>
            @empty<p>Este hotel no tiene una tarifa vigente.</p>@endforelse
        </div>
    </div>
</article>
@endforeach
</div>
@else
@if(count($vehiculos))
<section class="oferta-flota"><h2>Vehículos disponibles</h2><ul>@foreach($vehiculos as $vehiculo)<li><strong>{{ $vehiculo['tipo'] ?: 'Vehículo' }}</strong> {{ trim($vehiculo['marca'].' '.$vehiculo['modelo']) }} · hasta {{ $vehiculo['capacidad'] }} pasajeros</li>@endforeach</ul><p>El vehículo concreto lo confirma el equipo según la fecha.</p></section>
@endif
<div class="oferta-lista">
@foreach($servicios as $servicio)
@php $minutos = (int) ($servicio['duracionMin'] ?? 0); @endphp
<article class="oferta-card oferta-card-simple">
    <div>
        <span class="etiqueta">{{ $servicio['tipo'] ?: 'Traslado' }}</span>
        <h2>{{ $servicio['nombre'] }}</h2>
        <p>{{ $servicio['descripcion'] }}</p>
        <p class="oferta-meta">{{ $servicio['origen'] }} → {{ $servicio['destino'] }} @if($minutos)· {{ $minutos >= 60 ? intdiv($minutos, 60).' h'.($minutos % 60 ? ' '.($minutos % 60).' min' : '') : $minutos.' min' }}@endif @if($servicio['distanciaKm'])· {{ $servicio['distanciaKm'] }} km @endif @if($servicio['capacidad'])· hasta {{ $servicio['capacidad'] }} pasajeros @endif</p>
        <div class="oferta-precio"><b>{{ $servicio['precio'] }} {{ $servicio['moneda'] }}</b><small>precio base del servicio</small>
            @unless($esPersonal)<form method="POST" action="{{ route('publico.transporte.elegir') }}">@csrf<input type="hidden" name="servicio" value="{{ $servicio['codigo'] }}"><button class="boton pequeno" type="submit">Elegir este traslado</button></form>@endunless
        </div>
    </div>
</article>
@endforeach
</div>
@endif
</div>
@endsection
