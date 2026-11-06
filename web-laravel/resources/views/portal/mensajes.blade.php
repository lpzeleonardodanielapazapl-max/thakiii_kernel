@extends('layouts.portal')
@section('titulo', 'Mensajes')
@section('portal-clase', 'chat-pagina-cliente')
@section('portal')
<x-chat-assets/>
<div class="chat-pagina-titulo"><div><h1>Mensajes</h1><p>Tu próximo camino se construye conversando.</p></div></div>
<x-chat :messages="$mensajes" :contact="$asesor['nombre'] ?? 'Tu asesor de viaje'" :subtitle="count($asesor) ? 'Equipo Thaki · Conversación privada' : 'Pendiente de asignación'" :back="route('portal.inicio')" :action="route('portal.mensajes.enviar')" :enabled="count($asesor) > 0" label="Tu mensaje">
    <p>Aún no tienes asesor asignado. Puedes registrar tu solicitud mientras el equipo prepara tu atención.</p>
    <div class="acciones"><form method="POST" action="{{ route('portal.asignar') }}">@csrf<button type="submit" class="boton secundario pequeno">Buscar asesor disponible</button></form><a class="enlace" href="{{ route('portal.solicitud') }}">Crear una solicitud</a></div>
</x-chat>
@endsection
