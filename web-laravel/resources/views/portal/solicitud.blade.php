@extends('layouts.portal')
@section('titulo', 'Diseña tu viaje')
@section('portal')
<div class="portal-titulo"><div><span class="etiqueta">VIAJAMOS A TU RITMO</span><h1>¿Cómo imaginas tu viaje?</h1><p>Tu asesor usará estos detalles para preparar una propuesta personalizada.</p></div><a class="enlace" href="{{ route('portal.inicio') }}">← Mi viaje</a></div>
<form class="formulario tarjeta" method="POST" action="{{ route('portal.solicitar') }}">
    @csrf
    <p>Los campos con * son obligatorios. Las preferencias opcionales se pueden conversar con el asesor.</p>
    <fieldset><legend>Las primeras coordenadas</legend><div class="rejilla dos">
        @foreach(['fechaInicio' => 'Fecha de inicio', 'fechaFin' => 'Fecha de fin'] as $campo => $titulo)
        <label for="{{ $campo }}">{{ $titulo }} *<input id="{{ $campo }}" type="date" name="{{ $campo }}" min="{{ now()->format('Y-m-d') }}" value="{{ old($campo, $borrador[$campo] ?? '') }}" required @if($errors->has($campo)) aria-invalid="true" aria-describedby="error-{{ $campo }}" @endif><x-error-campo :name="$campo"/></label>
        @endforeach
        <label for="personas">Número de viajeros *<input id="personas" type="number" name="personas" min="1" max="1000" value="{{ old('personas', $borrador['personas'] ?? 1) }}" required @if($errors->has('personas')) aria-invalid="true" aria-describedby="error-personas" @endif><x-error-campo name="personas"/></label>
        <label for="destinos">Destinos que te interesan (opcional)<input id="destinos" name="destinos" maxlength="200" value="{{ old('destinos', $borrador['destinos'] ?? '') }}" placeholder="Por ejemplo: Uyuni y La Paz" @if($errors->has('destinos')) aria-invalid="true" aria-describedby="error-destinos" @endif><x-error-campo name="destinos"/></label>
        <label for="presupuesto">Presupuesto total estimado (opcional)<input id="presupuesto" type="number" name="presupuesto" min="0" max="9999999999.99" step="0.01" value="{{ old('presupuesto', $borrador['presupuesto'] ?? '') }}" @if($errors->has('presupuesto')) aria-invalid="true" aria-describedby="error-presupuesto" @endif><small>Para el conjunto de viajeros.</small><x-error-campo name="presupuesto"/></label>
        <label for="moneda">Moneda *<select id="moneda" name="moneda" required @if($errors->has('moneda')) aria-invalid="true" aria-describedby="error-moneda" @endif>@foreach(['BOB' => 'Bolivianos · BOB', 'USD' => 'Dólares · USD', 'EUR' => 'Euros · EUR'] as $codigo => $titulo)<option value="{{ $codigo }}" @selected(old('moneda', $borrador['moneda'] ?? 'USD') === $codigo)>{{ $titulo }}</option>@endforeach</select><x-error-campo name="moneda"/></label>
    </div></fieldset>
    <fieldset><legend>Un viaje que se parezca a ti</legend>
        <label for="intereses">Intereses y detalles importantes *<textarea id="intereses" name="intereses" maxlength="4000" required placeholder="Naturaleza, cultura, caminatas, ritmo del viaje, necesidades de accesibilidad…" @if($errors->has('intereses')) aria-invalid="true" aria-describedby="error-intereses" @endif>{{ old('intereses', $borrador['intereses'] ?? '') }}</textarea><x-error-campo name="intereses"/></label>
        <div class="rejilla tres espacio-superior">@foreach(['hotel' => 'Alojamiento', 'alimentacion' => 'Alimentación', 'transporte' => 'Transporte'] as $campo => $titulo)<label for="{{ $campo }}">{{ $titulo }} (opcional)<input id="{{ $campo }}" name="{{ $campo }}" maxlength="200" value="{{ old($campo, $borrador[$campo] ?? '') }}" placeholder="Tus preferencias" @if($errors->has($campo)) aria-invalid="true" aria-describedby="error-{{ $campo }}" @endif><x-error-campo :name="$campo"/></label>@endforeach</div>
    </fieldset>
    <p class="nota">Enviar esta solicitud inicia la preparación del viaje. La propuesta, sus servicios y el precio se revisan contigo antes de registrar la aceptación.</p>
    <button type="submit" class="boton" data-submitting-label="Enviando tu idea…">Enviar mi idea de viaje ↗</button>
</form>
@endsection
