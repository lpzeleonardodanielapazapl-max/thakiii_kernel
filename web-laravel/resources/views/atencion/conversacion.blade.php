@extends('layouts.admin')
@section('titulo', 'Mensajes')
@section('clase-pagina', 'chat-pagina-personal')
@section('contenido')
<x-chat-assets/>
<div class="chat-pagina-titulo"><div><h1>Mensajes</h1><p>Conversa los detalles del viaje con tu cliente.</p></div></div>
@include('components.avisos-publicos', ['compacto' => true])
<div class="acciones-pagina"><a class="boton boton-secundario" href="{{ isset($conversacion['clienteCodigo']) ? route('clientes.show', $conversacion['clienteCodigo']) : route('clientes.index') }}">Volver a la ficha del cliente</a><form method="POST" action="{{ route('atencion.leer', $conversacion['codigo']) }}">@csrf<button class="boton boton-secundario" type="submit">Marcar mensajes como leídos</button></form></div>
<x-chat :messages="$conversacion['mensajes']" :contact="$conversacion['cliente'] ?? 'Cliente'" :subtitle="$conversacion['asunto'] ?: 'Conversación privada'" :back="isset($conversacion['clienteCodigo']) ? route('clientes.show', $conversacion['clienteCodigo']) : route('clientes.index')" :action="route('atencion.responder', $conversacion['codigo'])" :client-id="$conversacion['clienteId']" :enabled="($conversacion['estado'] ?? 'ABIERTA') === 'ABIERTA'" label="Responder al cliente">
    <p>Esta conversación está cerrada. Puedes consultar el historial de mensajes.</p>
    <a class="enlace-registro" href="{{ isset($conversacion['clienteCodigo']) ? route('clientes.show', $conversacion['clienteCodigo']) : route('clientes.index') }}">Volver a la ficha del cliente →</a>
</x-chat>
@endsection
