@extends('layouts.admin')
@section('titulo', 'Mensajes')
@section('clase-pagina', 'chat-pagina-personal')
@section('contenido')
@php
    $solicitudActiva = collect($solicitudesChat ?? [])->first(fn ($s) => (int) ($s['propuestasAceptadas'] ?? 0) === 0);
    $reservaPendiente = collect($reservasChat ?? [])->firstWhere('estado', 'PENDIENTE_ANTICIPO');
@endphp
<x-chat-assets/>
<div class="chat-pagina-titulo"><div><h1>Mensajes</h1><p>Conversa los detalles del viaje y registra el cierre cuando esté listo.</p></div></div>
@include('components.avisos-publicos', ['compacto' => true])
<div class="acciones-pagina"><a class="boton boton-secundario" href="{{ isset($conversacion['clienteCodigo']) ? route('clientes.show', $conversacion['clienteCodigo']) : route('clientes.index') }}">Volver a la ficha del cliente</a><a class="boton boton-secundario" href="{{ route('atencion.viajesConfirmados') }}">Viajes confirmados</a><form method="POST" action="{{ route('atencion.leer', $conversacion['codigo']) }}">@csrf<button class="boton boton-secundario" type="submit">Marcar mensajes como leídos</button></form></div>
<div class="chat-con-formulario">
    <div class="chat-con-formulario-chat">
        <x-chat :messages="$conversacion['mensajes']" :contact="$conversacion['cliente'] ?? 'Cliente'" :subtitle="$conversacion['asunto'] ?: 'Conversación privada'" :back="isset($conversacion['clienteCodigo']) ? route('clientes.show', $conversacion['clienteCodigo']) : route('clientes.index')" :action="route('atencion.responder', $conversacion['codigo'])" :client-id="$conversacion['clienteId']" :enabled="($conversacion['estado'] ?? 'ABIERTA') === 'ABIERTA'" label="Responder al cliente">
            <p>Esta conversación está cerrada. Puedes consultar el historial de mensajes.</p>
            <a class="enlace-registro" href="{{ isset($conversacion['clienteCodigo']) ? route('clientes.show', $conversacion['clienteCodigo']) : route('clientes.index') }}">Volver a la ficha del cliente →</a>
        </x-chat>
    </div>
    <aside class="chat-con-formulario-panel panel" aria-label="Registro del viaje">
        <span class="sobre-titulo">MIENTRAS CONVERSAS</span>
        <h2>Registro del viaje</h2>
        @if($reservaPendiente)
            <p class="atencion-ayuda">Ya habilitaste el pago. El cliente puede abonar anticipo o total desde su pasarela.</p>
            <div class="atencion-caja">
                <h3>Reserva {{ $reservaPendiente['codigo'] }}</h3>
                <p><x-print-value :value="$reservaPendiente['total']" type="money" :currency="$reservaPendiente['moneda']"/></p>
                <a class="enlace" href="{{ route('atencion.reserva', $reservaPendiente['codigo']) }}">Ver reserva ↗</a>
            </div>
        @elseif($solicitudActiva)
            <p class="atencion-ayuda">Anota aquí lo acordado con el cliente. Al guardar se crea la reserva y se habilita la pasarela de pagos.</p>
            <form method="POST" action="{{ route('atencion.cerrarPago') }}" class="formulario-grid" data-save-form>
                @csrf
                <input type="hidden" name="conversacion" value="{{ $conversacion['codigo'] }}">
                <input type="hidden" name="solicitud" value="{{ $solicitudActiva['codigo'] }}">
                <div class="campo ancho-completo"><label for="titulo">Título del viaje *</label><input id="titulo" name="titulo" maxlength="200" value="{{ old('titulo', 'Viaje de '.$conversacion['cliente']) }}" required><x-error-campo name="titulo"/></div>
                <div class="campo ancho-completo"><label for="incluye">Qué incluye el viaje *</label><textarea id="incluye" name="incluye" rows="5" maxlength="4000" required placeholder="Guía, transporte, alimentación, entradas, noches de hotel…">{{ old('incluye') }}</textarea><x-error-campo name="incluye"/></div>
                <div class="campo ancho-completo"><label for="descripcion">Notas adicionales</label><textarea id="descripcion" name="descripcion" rows="3" maxlength="4000">{{ old('descripcion', $solicitudActiva['intereses'] ?? '') }}</textarea><x-error-campo name="descripcion"/></div>
                <div class="campo"><label for="precio">Precio total *</label><input type="number" id="precio" name="precio" min="0.01" max="9999999999.99" step="0.01" value="{{ old('precio', $solicitudActiva['presupuesto']) }}" required><x-error-campo name="precio"/></div>
                <div class="campo"><label for="moneda">Moneda *</label><select id="moneda" name="moneda" required>@foreach(['BOB'=>'BOB','USD'=>'USD','EUR'=>'EUR'] as $m=>$n)<option value="{{ $m }}" @selected(old('moneda', $solicitudActiva['moneda'] ?? 'USD')===$m)>{{ $n }}</option>@endforeach</select><x-error-campo name="moneda"/></div>
                <div class="campo"><label for="dias">Días del itinerario *</label><input type="number" id="dias" name="dias" min="1" max="120" value="{{ old('dias', max(1, \Illuminate\Support\Carbon::parse($solicitudActiva['fechaInicio'])->diffInDays(\Illuminate\Support\Carbon::parse($solicitudActiva['fechaFin'])) + 1)) }}" required><x-error-campo name="dias"/></div>
                <div class="formulario-acciones ancho-completo"><button type="submit" class="boton" data-submitting-label="Habilitando pago…">Registrar y habilitar pago</button></div>
            </form>
            <p class="atencion-ayuda espacio-superior">Solicitud {{ $solicitudActiva['codigo'] }} · {{ $solicitudActiva['personas'] }} viajeros · <x-print-value :value="$solicitudActiva['fechaInicio']" type="date"/> → <x-print-value :value="$solicitudActiva['fechaFin']" type="date"/></p>
        @else
            <p class="atencion-ayuda">No hay una solicitud abierta para cerrar. Cuando el cliente envíe su pedido de viaje, podrás registrar aquí el cierre y habilitar el pago.</p>
            @if(!empty($reservasChat))
                <div class="atencion-caja"><h3>Reservas del cliente</h3>@foreach($reservasChat as $r)<p>{{ $r['codigo'] }} · {{ $r['estado'] }} · <a class="enlace" href="{{ route('atencion.reserva', $r['codigo']) }}">abrir</a></p>@endforeach</div>
            @endif
        @endif
    </aside>
</div>
@endsection
