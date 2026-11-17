@extends('layouts.admin')
@section('titulo', 'Ficha del cliente')
@push('styles')<link rel="stylesheet" href="{{ asset('css/clientes.css') }}?v={{ filemtime(public_path('css/clientes.css')) }}">@endpush
@section('contenido')
<x-page-header :title="$cliente['nombre']" :description="$cliente['email'] ?: 'Sin correo registrado'">
    <a class="boton boton-secundario" href="{{ route('clientes.index') }}"><x-icon name="back"/> Clientes</a>
    @if($cliente['persona'] ?? null)<a class="boton boton-secundario" href="{{ route('personas.edit', $cliente['persona']) }}">Editar datos personales</a>@endif
    <a class="boton boton-secundario" href="{{ route('clientes.edit', $cliente['codigo']) }}">Editar registro del cliente</a>
</x-page-header>
@include('components.avisos-publicos')
<nav class="cliente-secciones" aria-label="Ficha del cliente"><a href="#datos">Datos</a><a href="#solicitudes">Solicitudes y propuestas</a><a href="#conversaciones">Chat</a><a href="#reservas">Reservas y pagos</a></nav>
<section class="panel" id="datos"><h2>Datos del cliente</h2><dl class="cliente-datos"><div><dt>Teléfono</dt><dd>{{ $cliente['telefono'] ?: 'Sin registrar' }}</dd></div><div><dt>Estado</dt><dd>{{ $cliente['estado'] }}</dd></div><div><dt>Registro</dt><dd><x-print-value :value="$cliente['registro']" type="datetime"/></dd></div><div><dt>Código</dt><dd>{{ $cliente['codigo'] }}</dd></div></dl>
<form method="POST" action="{{ route('clientes.revisar', $cliente['codigo']) }}">@csrf<button class="boton boton-secundario" type="submit">Marcar ficha, solicitudes y reservas como revisadas</button></form></section>
<section class="panel" id="solicitudes"><h2>Solicitudes y propuestas</h2><div class="cliente-tarjetas">@forelse($cliente['solicitudes'] as $s)<article><div><strong>{{ $s['codigo'] }}</strong><x-viaje-estado :value="$s['estado']"/></div><p><x-print-value :value="$s['inicio']" type="date"/> — <x-print-value :value="$s['fin']" type="date"/> · {{ $s['personas'] }} viajeros</p><p>{{ $s['intereses'] }}</p><p>Presupuesto <x-print-value :value="$s['presupuesto']" type="money" :currency="$s['moneda']"/></p><a class="boton boton-secundario" href="{{ route('atencion.solicitud', $s['codigo']) }}">Ver solicitud y propuestas <x-icon name="arrow"/></a></article>@empty<p>El cliente todavía no ha enviado solicitudes.</p>@endforelse</div></section>
<section class="panel" id="conversaciones"><h2>Conversaciones</h2><div class="cliente-tarjetas">@forelse($cliente['conversaciones'] as $c)<article><strong>{{ $c['asunto'] ?: 'Conversación privada' }}</strong><p>{{ $c['estado'] }} · <x-print-value :value="$c['fecha']" type="datetime"/></p><a class="boton boton-secundario" href="{{ route('atencion.conversacion', $c['codigo']) }}"><x-icon name="chat"/> Abrir chat</a></article>@empty<p>Todavía no hay conversaciones disponibles.</p>@endforelse</div></section>
<section class="panel" id="reservas"><h2>Reservas y pagos</h2><div class="cliente-tarjetas">@forelse($cliente['reservas'] as $r)<article><strong>{{ $r['codigo'] }}</strong><p><x-viaje-estado :value="$r['estado']"/></p><p><x-print-value :value="$r['inicio']" type="date"/> — <x-print-value :value="$r['fin']" type="date"/></p><p>Total <x-print-value :value="$r['total']" type="money" :currency="$r['moneda']"/></p><a class="boton boton-secundario" href="{{ route('atencion.reserva', $r['codigo']) }}">Ver reserva, pagos y cancelación</a></article>@empty<p>El cliente todavía no tiene reservas de viaje.</p>@endforelse</div></section>
<x-print-report title="Ficha de cliente" :reference="$cliente['codigo']" :subtitle="$cliente['nombre']">
    <p>{{ $cliente['email'] }} · {{ $cliente['telefono'] }} · {{ $cliente['estado'] }}</p>
    <h2>Solicitudes</h2><table class="reporte-tabla"><thead><tr><th>Código</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr></thead><tbody>@foreach($cliente['solicitudes'] as $s)<tr><td>{{ $s['codigo'] }}</td><td>{{ $s['inicio'] }}</td><td>{{ $s['fin'] }}</td><td>{{ $s['estado'] }}</td></tr>@endforeach</tbody></table>
    <h2>Reservas</h2><table class="reporte-tabla"><thead><tr><th>Código</th><th>Estado</th><th>Total</th></tr></thead><tbody>@foreach($cliente['reservas'] as $r)<tr><td>{{ $r['codigo'] }}</td><td>{{ $r['estado'] }}</td><td>{{ $r['total'] }} {{ $r['moneda'] }}</td></tr>@endforeach</tbody></table>
</x-print-report>
@endsection
