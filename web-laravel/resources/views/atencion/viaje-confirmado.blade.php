@extends('layouts.admin')
@section('titulo', 'Organizar viaje')
@section('contenido')
<header class="atencion-cabecera">
    <div><span class="sobre-titulo">VIAJE CONFIRMADO</span><h1>{{ $viaje['titulo'] }}</h1><p>{{ $viaje['cliente'] }} · {{ $viaje['codigo'] }}</p></div>
    <a class="boton boton-secundario" href="{{ route('atencion.viajesConfirmados') }}">← Listado</a>
</header>
@include('components.avisos-publicos')
<dl class="resumen-viaje">
    <div><dt>Inicio</dt><dd><x-print-value :value="$viaje['fechaInicio']" type="date"/></dd></div>
    <div><dt>Fin</dt><dd><x-print-value :value="$viaje['fechaFin']" type="date"/></dd></div>
    <div><dt>Viajeros</dt><dd>{{ $viaje['personas'] }}</dd></div>
    <div><dt>Total</dt><dd><x-print-value :value="$viaje['total']" type="money" :currency="$viaje['moneda']"/></dd></div>
</dl>
<div class="atencion-columnas">
    <section class="panel">
        <span class="sobre-titulo">EQUIPO EN CAMPO</span>
        <h2>Personal del viaje</h2>
        <form method="POST" action="{{ route('atencion.asignarPersonal', $viaje['codigo']) }}" class="formulario-grid">
            @csrf
            <div class="campo"><label for="empleado">Persona *</label>
                <select id="empleado" name="empleado" required>
                    <option value="">Selecciona…</option>
                    @foreach($viaje['personalDisponible'] ?? [] as $p)
                        <option value="{{ $p['codigo'] }}">{{ $p['nombre'] }}@if($p['viajesSolapados']) ({{ $p['viajesSolapados'] }} viaje(s) en esas fechas)@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="campo"><label for="rol">Rol *</label>
                <select id="rol" name="rol" required>
                    @foreach(['GUIA'=>'Guía','CONDUCTOR'=>'Conductor','COCINERO'=>'Cocinero','OTRO'=>'Otro'] as $v=>$t)
                        <option value="{{ $v }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo ancho-completo"><label for="notas-personal">Notas</label><input id="notas-personal" name="notas" maxlength="500" value="{{ old('notas') }}"></div>
            <div class="formulario-acciones ancho-completo"><button class="boton" type="submit">Asignar personal</button></div>
        </form>
        <ul class="lista-asignaciones">
            @forelse($viaje['personal'] ?? [] as $asig)
                <li>
                    <div><strong>{{ $asig['nombre'] }}</strong> · {{ $asig['rol'] }}@if($asig['notas'])<br><small>{{ $asig['notas'] }}</small>@endif</div>
                    <form method="POST" action="{{ route('atencion.quitarPersonal', [$viaje['codigo'], $asig['id']]) }}">@csrf @method('DELETE')<button class="boton boton-secundario boton-pequeno" type="submit">Quitar</button></form>
                </li>
            @empty
                <li><p class="atencion-ayuda">Aún no hay personal asignado.</p></li>
            @endforelse
        </ul>
    </section>
    <section class="panel">
        <span class="sobre-titulo">MATERIAL DEL DÍA</span>
        <h2>Utilería / equipo</h2>
        <p class="atencion-ayuda">La disponibilidad descuenta lo ya reservado en viajes confirmados que se solapan con estas fechas.</p>
        <form method="POST" action="{{ route('atencion.asignarUtileria', $viaje['codigo']) }}" class="formulario-grid">
            @csrf
            <div class="campo"><label for="producto">Producto *</label>
                <select id="producto" name="producto" required>
                    <option value="">Selecciona…</option>
                    @foreach($viaje['utileriaDisponible'] ?? [] as $u)
                        @php $disp = max(0, (float)$u['stock'] - (float)$u['reservado']); @endphp
                        <option value="{{ $u['codigo'] }}" @disabled($disp < 1)>{{ $u['nombre'] }} · disponible {{ $disp }} (stock {{ $u['stock'] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="campo"><label for="cantidad">Cantidad *</label><input id="cantidad" type="number" name="cantidad" min="1" value="{{ old('cantidad', 1) }}" required></div>
            <div class="campo ancho-completo"><label for="notas-utileria">Notas</label><input id="notas-utileria" name="notas" maxlength="500"></div>
            <div class="formulario-acciones ancho-completo"><button class="boton" type="submit">Reservar utilería</button></div>
        </form>
        <ul class="lista-asignaciones">
            @forelse($viaje['utileria'] ?? [] as $item)
                <li>
                    <div><strong>{{ $item['nombre'] }}</strong> · {{ $item['cantidad'] }}@if($item['notas'])<br><small>{{ $item['notas'] }}</small>@endif</div>
                    <form method="POST" action="{{ route('atencion.quitarUtileria', [$viaje['codigo'], $item['id']]) }}">@csrf @method('DELETE')<button class="boton boton-secundario boton-pequeno" type="submit">Liberar</button></form>
                </li>
            @empty
                <li><p class="atencion-ayuda">Aún no hay utilería reservada.</p></li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
