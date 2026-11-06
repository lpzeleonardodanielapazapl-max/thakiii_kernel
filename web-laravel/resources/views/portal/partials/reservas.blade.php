<section class="tarjeta"><h2>Reservas y pagos registrados</h2>
    @forelse($reservas as $r)
    <article class="fila"><div style="width:100%"><x-viaje-estado :value="$r['estado']"/><h3 class="espacio-superior">Tu reserva · <x-print-value :value="$r['fechaInicio']" type="date"/></h3><p><x-print-value :value="$r['fechaInicio']" type="date"/> → <x-print-value :value="$r['fechaFin']" type="date"/> · {{ $r['personas'] }} {{ $r['personas'] == 1 ? 'viajero' : 'viajeros' }}</p>
        <dl class="datos">@foreach(['total' => 'Total del viaje', 'anticipo' => 'Anticipo requerido · 50 %', 'pagado' => 'Pagos verificados', 'saldo' => 'Saldo pendiente'] as $campo => $titulo)<div><dt>{{ $titulo }}</dt><dd><x-print-value :value="$r[$campo]" type="money" :currency="$r['moneda']"/></dd></div>@endforeach</dl>
        @if($r['estado'] === 'PENDIENTE_ANTICIPO')<p>Coordina el anticipo externo con tu asesor. El personal debe verificarlo para confirmar el viaje.</p>
        @elseif($r['estado'] === 'CONFIRMADA')<p>Anticipo verificado: tu viaje está confirmado. {{ (float)$r['saldo'] > 0 ? 'El saldo se coordina para la llegada.' : 'Los pagos registrados cubren el total del viaje.' }}</p>
        @elseif($r['estado'] === 'CANCELADA')<p>La cancelación fue registrada por el personal. Se conserva el historial de pagos, sin devolución.</p>
        @elseif($r['estado'] === 'FINALIZADA')<p>Este viaje ha finalizado. Aquí se conserva el resumen de su reserva y sus pagos.</p>@endif
        <small class="referencia-viaje">Referencia: {{ $r['codigo'] }}</small>
        @if(in_array($r['estado'], ['PENDIENTE_ANTICIPO', 'CONFIRMADA']))<details class="espacio-superior"><summary>Solicitar cancelación</summary><p>La solicitud se revisa con el administrativo. No se realizan devoluciones.</p><form class="formulario" method="POST" action="{{ route('portal.cancelacion', $r['codigo']) }}">@csrf<label>Motivo<textarea name="contenido" maxlength="2000" required></textarea></label><button type="submit" class="boton secundario pequeno">Enviar solicitud al asesor</button></form></details>@endif
    </div></article>
    @empty<p>Aquí verás tus reservas y los pagos que haya verificado el personal.</p>@endforelse
</section>
