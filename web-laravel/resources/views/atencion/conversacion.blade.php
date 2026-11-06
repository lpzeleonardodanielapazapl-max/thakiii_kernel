@extends('layouts.admin')
@section('titulo', 'Mensajes')
@section('clase-pagina', 'chat-pagina-personal')
@section('contenido')
<x-chat-assets/>
<div class="chat-pagina-titulo"><div><h1>Mensajes</h1><p>Conversa los detalles del viaje con tu cliente.</p></div></div>
@include('components.avisos-publicos', ['compacto' => true])
<x-chat :messages="$conversacion['mensajes']" :contact="$conversacion['cliente'] ?? 'Cliente'" :subtitle="$conversacion['asunto'] ?: 'Conversación privada'" :back="route('atencion.index')" :action="route('atencion.responder', $conversacion['codigo'])" :client-id="$conversacion['clienteId']" :enabled="($conversacion['estado'] ?? 'ABIERTA') === 'ABIERTA'" label="Responder al cliente">
    <p>Esta conversación está cerrada. Puedes consultar el historial de mensajes.</p>
    <a class="enlace-registro" href="{{ route('atencion.index') }}">Volver a Atención a clientes →</a>
</x-chat>
@endsection
