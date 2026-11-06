@extends('layouts.admin')
@section('titulo', 'Conversación con cliente')
@section('contenido')
<x-page-header :title="$conversacion['asunto']" description="Conversación privada con el cliente." eyebrow="ATENCIÓN A CLIENTES"><a class="boton boton-secundario" href="{{ route('atencion.index') }}">← Atención a clientes</a></x-page-header>
@include('components.avisos-publicos')
<section class="panel"><h2>Últimos 100 mensajes</h2>@forelse(array_reverse($conversacion['mensajes']) as $m)<article style="padding:16px 0;border-bottom:1px solid #ddd"><strong>{{ (string)$m['remitente'] === (string)$conversacion['clienteId'] ? 'Cliente' : 'Equipo Thaki' }}</strong><p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $m['contenido'] }}</p><small><x-print-value :value="$m['fecha']" type="datetime"/></small></article>@empty<p>La conversación aún no tiene mensajes.</p>@endforelse</section><section class="panel formulario-panel"><h2>Responder al cliente</h2><form method="POST" action="{{ route('atencion.responder', $conversacion['codigo']) }}" class="formulario-grid">@csrf<div class="campo ancho-completo"><label for="contenido">Mensaje</label><textarea id="contenido" name="contenido" rows="5" maxlength="4000" required>{{ old('contenido') }}</textarea></div><div class="formulario-acciones"><button class="boton">Enviar respuesta</button></div></form></section>
@endsection
