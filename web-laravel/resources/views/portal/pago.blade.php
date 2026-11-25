@extends('layouts.portal')
@section('titulo', 'Pagar viaje')
@section('portal')
@php
    $anticipo = (float) ($reserva['anticipo'] ?? 0);
    $saldo = (float) ($reserva['saldo'] ?? 0);
    $pagado = (float) ($reserva['pagado'] ?? 0);
@endphp
<div class="portal-titulo"><div><span class="etiqueta">PASARELA THAKI</span><h1>Paga tu viaje</h1><p>Reserva {{ $reserva['codigo'] }} · <x-print-value :value="$reserva['total']" type="money" :currency="$reserva['moneda']"/></p></div><a class="enlace" href="{{ route('portal.inicio') }}">← Mi viaje</a></div>
@include('components.avisos-publicos')
<section class="tarjeta formulario-panel">
    <dl class="resumen-viaje">
        <div><dt>Pagado</dt><dd><x-print-value :value="$pagado" type="money" :currency="$reserva['moneda']"/></dd></div>
        <div><dt>Anticipo 50 %</dt><dd><x-print-value :value="$anticipo" type="money" :currency="$reserva['moneda']"/></dd></div>
        <div><dt>Saldo</dt><dd><x-print-value :value="$saldo" type="money" :currency="$reserva['moneda']"/></dd></div>
        <div><dt>Estado</dt><dd>{{ $reserva['estado'] }}</dd></div>
    </dl>
    @if($saldo <= 0)
        <p>Esta reserva ya está pagada.</p>
    @else
        <form method="POST" action="{{ route('portal.pagar', $reserva['codigo']) }}" class="formulario-grid" data-save-form>
            @csrf
            <div class="campo ancho-completo"><label for="tipo">Tipo de pago *</label>
                <select id="tipo" name="tipo" required>
                    <option value="ANTICIPO" @selected(old('tipo')==='ANTICIPO')>Anticipo (50 %)</option>
                    <option value="TOTAL" @selected(old('tipo', 'TOTAL')==='TOTAL')>Pago total del saldo</option>
                </select>
                <x-error-campo name="tipo"/>
            </div>
            <div class="campo ancho-completo"><label for="titular">Titular de la tarjeta *</label><input id="titular" name="titular" maxlength="120" value="{{ old('titular') }}" required><x-error-campo name="titular"/></div>
            <div class="campo"><label for="tarjeta">Número de tarjeta *</label><input id="tarjeta" name="tarjeta" inputmode="numeric" autocomplete="cc-number" maxlength="19" value="{{ old('tarjeta') }}" required placeholder="Solo dígitos"><x-error-campo name="tarjeta"/></div>
            <div class="campo"><label for="vencimiento">Vencimiento *</label><input id="vencimiento" name="vencimiento" maxlength="5" value="{{ old('vencimiento') }}" required placeholder="MM/AA"><x-error-campo name="vencimiento"/></div>
            <div class="campo"><label for="cvv">CVV *</label><input id="cvv" name="cvv" inputmode="numeric" maxlength="4" value="{{ old('cvv') }}" required><x-error-campo name="cvv"/></div>
            <div class="formulario-acciones ancho-completo"><button class="boton" type="submit" data-submitting-label="Procesando…">Confirmar pago</button><small class="atencion-ayuda">Simulación de pasarela interna para el anticipo o el total. No se envían datos a un banco externo.</small></div>
        </form>
    @endif
</section>
@endsection
