@extends('layouts.portal')
@section('titulo', 'Notificaciones')
@section('portal')
<span class="etiqueta">LO NUEVO EN TU CAMINO</span><h1>Tus notificaciones.</h1><section class="tarjeta"><ul class="lista-notificaciones">@forelse($notificaciones as $n)<li><span class="estado">{{ $n['leida'] ? 'LEÍDA' : 'NUEVA' }}</span><h2 class="espacio-superior">{{ $n['titulo'] }}</h2><p class="texto-preservado">{{ $n['contenido'] }}</p><p class="fecha"><x-print-value :value="$n['fecha']" type="datetime"/></p>@if(!$n['leida'])<form method="POST" action="{{ route('portal.notificaciones.leer', $n['codigo']) }}">@csrf<button class="boton secundario pequeno">Marcar como leída</button></form>@endif</li>@empty<li><h2>Todo al día.</h2><p>Las novedades de tu viaje aparecerán aquí.</p></li>@endforelse</ul></section>
@endsection
