@props(['estado' => [], 'enPerfil' => false])
@once
@push('styles')<link rel="stylesheet" href="{{ asset('css/espacio-viaje.css') }}?v={{ filemtime(public_path('css/espacio-viaje.css')) }}" media="screen">@endpush
@endonce
@php($porcentaje = max(0, min(100, (int)($estado['porcentaje'] ?? 0))))
<section class="perfil-progreso" aria-label="Estado de tu perfil">
    <div class="perfil-progreso-titulo"><h2>{{ ($estado['completo'] ?? false) ? 'Perfil completo' : 'Completa tu perfil' }}</h2><strong>{{ $porcentaje }} %</strong></div>
    <progress value="{{ $porcentaje }}" max="100" aria-label="Datos de viaje completados">{{ $porcentaje }} %</progress>
    @if($estado['completo'] ?? false)<p>Ya puedes enviar tu solicitud de viaje.</p>
    @else<p>Completa el 100 % de los datos necesarios para enviar tu pedido. El género es opcional.</p>
        @if($enPerfil && count($estado['faltantes'] ?? []))<ul>@foreach($estado['faltantes'] as $indice => $etiqueta)<li><a href="#{{ $estado['camposFaltantes'][$indice] ?? '' }}">{{ $etiqueta }}</a></li>@endforeach</ul>@endif
    @endif
    @unless($enPerfil)<a class="enlace" href="{{ route('portal.perfil') }}">{{ ($estado['completo'] ?? false) ? 'Revisar mis datos' : 'Completar mis datos' }} ↗</a>@endunless
</section>
