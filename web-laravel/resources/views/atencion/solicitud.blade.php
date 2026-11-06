@extends('layouts.admin')
@section('titulo', 'Solicitud de viaje')
@section('contenido')
@php
    $aceptada = collect($solicitud['propuestas'])->contains('estado', 'ACEPTADA');
    $diasFormulario = old('dias', $propuestaBase['itinerario'] ?? [['descripcion' => '', 'servicios' => '']]);
    $diasFormulario = is_array($diasFormulario) ? array_values(array_filter($diasFormulario, 'is_array')) : [];
    if (!$diasFormulario) {
        $diasFormulario = [['descripcion' => '', 'servicios' => '']];
    }
@endphp
<header class="atencion-cabecera">
    <div><span class="sobre-titulo">ATENCIÓN · VIAJE A MEDIDA</span><h1>El viaje de {{ $solicitud['nombres'] }} {{ $solicitud['apellidos'] }}</h1><p>{{ $solicitud['email'] }}</p><small class="referencia-viaje">Referencia: {{ $solicitud['codigo'] }}</small></div>
    <a class="boton boton-secundario" href="{{ route('atencion.index') }}">← Atención a clientes</a>
</header>
@include('components.avisos-publicos')
<dl class="resumen-viaje">
    <div><dt>Inicio del viaje</dt><dd><x-print-value :value="$solicitud['fechaInicio']" type="date"/></dd></div>
    <div><dt>Fin del viaje</dt><dd><x-print-value :value="$solicitud['fechaFin']" type="date"/></dd></div>
    <div><dt>Viajeros</dt><dd>{{ $solicitud['personas'] }} {{ $solicitud['personas'] == 1 ? 'persona' : 'personas' }}</dd></div>
    <div><dt>Presupuesto total estimado</dt><dd>@if($solicitud['presupuesto'] !== null)<x-print-value :value="$solicitud['presupuesto']" type="money" :currency="$solicitud['moneda']"/>@else Por definir @endif</dd></div>
</dl>
<div class="atencion-columnas">
    <div class="atencion-columna">
        @if(!count($solicitud['reservas']) && !$aceptada)
        <section class="panel formulario-panel" id="preparar-propuesta">
            <span class="sobre-titulo">EL SIGUIENTE PASO</span><h2>{{ $propuestaBase ? 'Ajustar y reenviar propuesta' : 'Preparar una propuesta' }}</h2>
            <p class="atencion-ayuda">{{ $propuestaBase ? 'Modifica los detalles que pidió el cliente sobre la propuesta ya enviada.' : 'Completa el recorrido por días y revisa los servicios antes de enviarlo.' }} Los campos con * son obligatorios.</p>
            @if($propuestaBase)<div class="atencion-caja"><h3>Trabajando sobre la propuesta {{ $propuestaBase['version'] }}</h3><p class="atencion-ayuda">Ya cargamos su título, descripción, precio e itinerario. Al reenviar se guardará una nueva versión; la siguiente es la <strong>propuesta {{ $siguienteVersion }}</strong>. El contenido enviado anteriormente se conserva.</p></div>@endif
            <form method="POST" action="{{ route('atencion.proponer', $solicitud['codigo']) }}" class="formulario-grid" data-save-form>
                @csrf
                <div class="campo ancho-completo"><label for="titulo">Título de la propuesta *</label><input id="titulo" name="titulo" maxlength="200" value="{{ old('titulo', $propuestaBase['titulo'] ?? '') }}" required @if($errors->has('titulo')) aria-invalid="true" aria-describedby="error-titulo" @endif><x-error-campo name="titulo"/></div>
                <div class="campo ancho-completo"><label for="descripcion">Descripción general *</label><textarea id="descripcion" name="descripcion" rows="4" maxlength="4000" required @if($errors->has('descripcion')) aria-invalid="true" aria-describedby="error-descripcion" @endif>{{ old('descripcion', $propuestaBase['descripcion'] ?? '') }}</textarea><x-error-campo name="descripcion"/></div>
                <div class="campo"><label for="precio">Precio total del viaje *</label><input type="number" id="precio" name="precio" min="0.01" max="9999999999.99" step="0.01" value="{{ old('precio', $propuestaBase['precio'] ?? '') }}" required @if($errors->has('precio')) aria-invalid="true" aria-describedby="error-precio" @endif><x-error-campo name="precio"/></div>
                <div class="campo"><label for="moneda">Moneda *</label><select id="moneda" name="moneda" required @if($errors->has('moneda')) aria-invalid="true" aria-describedby="error-moneda" @endif>@foreach(['BOB' => 'Bolivianos · BOB', 'USD' => 'Dólares · USD', 'EUR' => 'Euros · EUR'] as $moneda => $nombre)<option value="{{ $moneda }}" @selected(old('moneda', $propuestaBase['moneda'] ?? $solicitud['moneda']) === $moneda)>{{ $nombre }}</option>@endforeach</select><x-error-campo name="moneda"/></div>
                <div class="ancho-completo" data-itinerario-editor>
                    <h3>Itinerario día a día</h3><p class="atencion-ayuda">Cada día tiene su propio recorrido y sus servicios. El orden que ves será el que reciba el cliente.</p>
                    <x-error-campo name="dias"/>
                    <div class="editor-dias" data-dias-lista>@foreach($diasFormulario as $index => $dia)<x-itinerario-dia :index="$index" :dia="$dia"/>@endforeach</div>
                    <template data-dia-template><x-itinerario-dia index="__INDEX__"/></template>
                    <div class="editor-herramientas"><button type="button" class="boton boton-secundario" data-dia-agregar hidden>+ Añadir día</button><p data-dia-cantidad role="status" aria-live="polite">{{ count($diasFormulario) }} {{ count($diasFormulario) === 1 ? 'día' : 'días' }} en el itinerario</p></div>
                </div>
                <div class="formulario-acciones ancho-completo"><button type="submit" class="boton" data-submitting-label="Enviando propuesta…">{{ $propuestaBase ? 'Reenviar propuesta al cliente' : 'Enviar propuesta al cliente' }}</button><small class="atencion-ayuda">Se guardará como propuesta {{ $siguienteVersion }} y se notificará al cliente.</small></div>
            </form>
        </section>
        @endif
        <section class="panel" id="historial-propuestas">
            <span class="sobre-titulo">CADA VERSIÓN, CON SU DETALLE</span><h2>Historial de propuestas <span class="total-badge">{{ count($solicitud['propuestas']) }}</span></h2>
            <p class="atencion-ayuda">Abre una versión para revisar exactamente qué itinerario, servicios y precio recibió el cliente.</p>
            @forelse($solicitud['propuestas'] as $p)
            <details class="version-propuesta" @if($loop->first) open @endif>
                <summary><div><x-viaje-estado :value="$p['estado']"/><h3>{{ $p['titulo'] }}</h3><p>Versión {{ $p['version'] }} · {{ $p['dias'] ?? count($p['itinerario'] ?? []) }} días · <x-print-value :value="$p['precio']" type="money" :currency="$p['moneda']"/></p>@if(!empty($p['fechaEnvio']))<small>Enviada el <x-print-value :value="$p['fechaEnvio']" type="datetime"/></small>@endif</div></summary>
                <div class="version-contenido">
                    <p class="atencion-texto">{{ $p['descripcion'] ?? '' }}</p>
                    <ol class="version-itinerario">@forelse($p['itinerario'] ?? [] as $dia)<li><h4>Día {{ $dia['dia'] }}</h4><p>{{ $dia['descripcion'] }}</p><small>Servicios incluidos</small><p>{{ $dia['servicios'] ?: 'No especificados en esta versión.' }}</p></li>@empty<li><p>Esta versión no tiene días detallados.</p></li>@endforelse</ol>
                    <small class="referencia-viaje">Referencia de la versión: {{ $p['codigo'] }}</small>
                    @if(!$aceptada && !count($solicitud['reservas']))<div class="acciones-viaje espacio-superior"><a class="boton boton-secundario" href="{{ ($propuestaBase['codigo'] ?? null) === $p['codigo'] ? '#preparar-propuesta' : route('atencion.solicitud', ['codigo' => $solicitud['codigo'], 'editar' => $p['codigo']]).'#preparar-propuesta' }}">{{ $p['estado'] === 'ENVIADA' ? 'Modificar y reenviar' : 'Usar propuesta '.$p['version'].' como base' }}</a></div>@endif
                    @if($p['estado'] === 'ENVIADA' && !$aceptada && !count($solicitud['reservas']))
                    <form class="version-aceptar" method="POST" action="{{ route('atencion.aceptar', $p['codigo']) }}">@csrf
                        <label class="check-tarjeta"><input type="checkbox" name="confirmar" value="1" required> El cliente comunicó su aceptación de la versión {{ $p['version'] }}.</label>
                        <p class="atencion-ayuda">La aceptación deja la reserva pendiente del anticipo obligatorio del 50 %.</p>
                        <button type="submit" class="boton boton-secundario" data-submitting-label="Registrando aceptación…">Registrar aceptación del cliente</button>
                    </form>
                    @endif
                </div>
            </details>
            @empty<div class="atencion-caja"><h3>Todo listo para preparar la primera propuesta.</h3><p class="atencion-ayuda">Cuando la envíes, aparecerá aquí con su número de versión y el itinerario completo.</p></div>@endforelse
        </section>
    </div>
    <aside class="atencion-columna">
        <section class="panel"><span class="sobre-titulo">LO QUE BUSCA EL VIAJERO</span><h2>Preferencias del cliente</h2><h3>Intereses y detalles</h3><p class="atencion-texto">{{ $solicitud['intereses'] }}</p>
        @foreach($solicitud['preferencias'] as $pref)<div class="atencion-caja"><h3>{{ ['HOTEL' => 'Alojamiento', 'ALIMENTACION' => 'Alimentación', 'TRANSPORTE' => 'Transporte'][$pref['tipo']] ?? ucfirst(mb_strtolower($pref['tipo'])) }}</h3><p class="atencion-texto">{{ $pref['valor'] }}</p></div>@endforeach</section>
        @if(count($solicitud['reservas']))<section class="panel"><span class="sobre-titulo">CONTINUAR LA ATENCIÓN</span><h2>Reservas vinculadas</h2>@foreach($solicitud['reservas'] as $r)<div class="atencion-caja"><x-viaje-estado :value="$r['estado']"/><p class="atencion-ayuda"><x-print-value :value="$r['total']" type="money" :currency="$r['moneda']"/></p><a class="enlace-registro" href="{{ route('atencion.reserva', $r['codigo']) }}">Revisar pagos y reserva ↗</a><small class="referencia-viaje">{{ $r['codigo'] }}</small></div>@endforeach</section>@endif
    </aside>
</div>
@endsection
