@extends('layouts.portal')
@section('titulo', 'Mi viaje')
@section('portal-clase', 'viaje-con-chat')
@section('portal')
<x-chat-assets/>
@php
    $reservaActiva = collect($reservas)->first(fn($r) => in_array($r['estado'], ['PENDIENTE_ANTICIPO', 'CONFIRMADA']));
    $propuestaPendiente = collect($propuestas)->firstWhere('estado', 'ENVIADA');
    $propuestasActuales = array_values(array_filter($propuestas, fn($p) => in_array($p['estado'], ['ENVIADA', 'ACEPTADA'])));
    $propuestasAnteriores = array_values(array_filter($propuestas, fn($p) => !in_array($p['estado'], ['ENVIADA', 'ACEPTADA'])));
@endphp
<div class="viaje-espacio">
<div class="viaje-contenido">
<div class="portal-titulo"><div><span class="etiqueta">TU ESPACIO, TU CAMINO</span><h1>Hola, {{ $perfil['nombres'] ?? session('usuario.nombre') }}.</h1><p>Aquí construimos tu próximo viaje, paso a paso.</p></div><a class="boton {{ count($solicitudes) || count($reservas) ? 'secundario' : '' }}" href="{{ route('portal.solicitud') }}">Diseñar un viaje +</a></div>
<x-perfil-progreso :estado="$perfil['completitud'] ?? []"/>
@if(!count($solicitudes) && !count($reservas))
<section class="portal-bienvenida"><span class="etiqueta clara">TODO COMIENZA CON UNA IDEA</span><h2>Bolivia te espera.<br>Cuéntanos cómo quieres vivirla.</h2><p>Elige tus fechas, comparte tus intereses y deja que tu asesor prepare una propuesta para ti.</p><a class="boton" href="{{ route('portal.solicitud') }}">Crear mi primera solicitud ↗</a></section>
@elseif($reservaActiva)
<section class="siguiente-paso" aria-label="Siguiente paso de tu viaje"><span class="etiqueta">TU VIAJE, PASO A PASO</span>
    @if($reservaActiva['estado'] === 'CONFIRMADA')<h2>Tu viaje está confirmado.</h2><p>El personal ya verificó el anticipo. @if((float)$reservaActiva['saldo'] > 0)Tu saldo pendiente es de <x-print-value :value="$reservaActiva['saldo']" type="money" :currency="$reservaActiva['moneda']"/>; coordina su pago a la llegada.@else Los pagos registrados cubren el total de esta reserva.@endif</p>
    @else<h2>El siguiente paso es coordinar tu anticipo.</h2><p>Tu aceptación está registrada. Coordina con el asesor el anticipo de <x-print-value :value="$reservaActiva['anticipo']" type="money" :currency="$reservaActiva['moneda']"/>. El viaje se confirma después de que el personal verifique el pago externo.</p>@endif
    <a class="enlace" href="{{ route('portal.mensajes') }}">Conversar con mi asesor ↗</a>
</section>
@elseif($propuestaPendiente)
<section class="siguiente-paso"><span class="etiqueta">TIENES UNA PROPUESTA POR REVISAR</span><h2>{{ $propuestaPendiente['titulo'] }}</h2><p>Revisa el recorrido, los servicios y el precio. Puedes pedir ajustes antes de comunicar tu aceptación.</p><a class="boton pequeno" href="{{ route('portal.propuesta', $propuestaPendiente['codigo']) }}">Revisar mi propuesta ↗</a></section>
@elseif(count($solicitudes))
<section class="siguiente-paso"><span class="etiqueta">TU IDEA YA ESTÁ EN CAMINO</span><h2>Recibimos tus preferencias.</h2><p>Tu propuesta aparecerá aquí cuando el asesor la prepare. Mientras tanto puedes conversar sobre los detalles del viaje.</p><a class="enlace" href="{{ route('portal.mensajes') }}">Abrir conversación ↗</a></section>
@endif
<div class="viaje-detalles">
    <div class="pila">
        @if(count($reservas))@include('portal.partials.reservas')@endif
        <section class="tarjeta"><h2>Tus propuestas</h2>
            @forelse($propuestasActuales as $p)@include('portal.partials.propuesta-resumen')@empty<p>{{ count($propuestasAnteriores) ? 'No tienes propuestas pendientes de respuesta.' : 'Tu propuesta aparecerá aquí cuando el asesor la prepare.' }}</p>@endforelse
            @if(count($propuestasAnteriores))<details class="propuestas-anteriores"><summary>Versiones anteriores ({{ count($propuestasAnteriores) }})</summary>@foreach($propuestasAnteriores as $p)@include('portal.partials.propuesta-resumen')@endforeach</details>@endif
        </section>
        <section class="tarjeta"><h2>Solicitudes de viaje</h2>
            @forelse($solicitudes as $s)<article class="fila"><div><x-viaje-estado :value="$s['estado']"/><h3 class="espacio-superior"><x-print-value :value="$s['fechaInicio']" type="date"/> → <x-print-value :value="$s['fechaFin']" type="date"/></h3><p>{{ $s['personas'] }} {{ $s['personas'] == 1 ? 'viajero' : 'viajeros' }}</p><p class="texto-preservado">{{ $s['intereses'] }}</p><small class="referencia-viaje">Referencia: {{ $s['codigo'] }}</small></div></article>@empty<p>No has registrado solicitudes todavía.</p>@endforelse
        </section>
        @if(!count($reservas))@include('portal.partials.reservas')@endif
    </div>
</div>
</div>
<aside class="viaje-lateral" aria-label="Perfil y conversación con tu asesor">
    <div class="viaje-chat-titulo"><div><span class="etiqueta">CONTIGO EN EL CAMINO</span><h2>Habla con tu asesor</h2></div><a class="enlace" href="{{ route('portal.mensajes') }}">Ampliar ↗</a></div>
    <x-chat :messages="$mensajes" :contact="$asesor['nombre'] ?? 'Tu asesor de viaje'" :subtitle="count($asesor) ? 'Equipo Thaki · Conversación privada' : 'Pendiente de asignación'" :back="route('portal.inicio')" :action="route('portal.mensajes.enviar')" :enabled="count($asesor) > 0" label="Tu mensaje">
        <p>Aún no tienes asesor asignado. El equipo te acompañará con tu viaje.</p><form method="POST" action="{{ route('portal.asignar') }}">@csrf<button class="boton secundario pequeno" type="submit">Buscar asesor disponible</button></form>
    </x-chat>
    <a class="viaje-novedades" href="{{ route('portal.notificaciones') }}">Notificaciones <span>{{ count(array_filter($notificaciones, fn($n) => !$n['leida'])) }} sin leer ↗</span></a>
</aside>
</div>
@endsection
