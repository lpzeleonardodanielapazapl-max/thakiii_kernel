@extends('layouts.admin')
@section('titulo', 'Viajes confirmados')
@section('contenido')
<header class="atencion-cabecera">
    <div><span class="sobre-titulo">OPERACIONES</span><h1>Clientes con viaje confirmado</h1><p>Asigna personal y utilería según las fechas de cada viaje.</p></div>
</header>
@include('components.avisos-publicos')
<section class="panel panel-tabla">
    <div class="table-tools"><h2>Listado <span class="total-badge">{{ count($viajes) }}</span></h2></div>
    @if(!count($viajes))
        <p class="atencion-ayuda" style="padding:24px">Todavía no hay viajes confirmados. Aparecen aquí cuando el cliente completa el anticipo o el pago total.</p>
    @else
        <div class="tabla-deslizante">
            <table class="tabla">
                <thead><tr><th>Viaje</th><th>Cliente</th><th>Fechas</th><th>Total</th><th>Personal</th><th>Utilería</th><th></th></tr></thead>
                <tbody>
                @foreach($viajes as $viaje)
                    <tr>
                        <td><strong>{{ $viaje['titulo'] }}</strong><br><small>{{ $viaje['codigo'] }}</small></td>
                        <td>{{ $viaje['cliente'] }}<br><small>{{ $viaje['clienteCodigo'] }}</small></td>
                        <td><x-print-value :value="$viaje['fechaInicio']" type="date"/> → <x-print-value :value="$viaje['fechaFin']" type="date"/><br><small>{{ $viaje['personas'] }} viajeros</small></td>
                        <td><x-print-value :value="$viaje['total']" type="money" :currency="$viaje['moneda']"/></td>
                        <td>{{ $viaje['personal'] }}</td>
                        <td>{{ $viaje['utileria'] }}</td>
                        <td><a class="enlace" href="{{ route('atencion.viajeConfirmado', $viaje['codigo']) }}">Organizar ↗</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
